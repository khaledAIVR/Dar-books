<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemapCategoriesToExcel extends Command
{
    protected $signature = 'categories:remap-to-excel {--commit : Apply changes (default is dry-run)} {--delete-old : Delete old categories after remap (only with --commit)}';

    protected $description = 'Remap existing DB category links to the canonical Excel category list.';

    public function handle(): int
    {
        $commit = (bool)$this->option('commit');
        $deleteOld = (bool)$this->option('delete-old');

        if ($deleteOld && !$commit) {
            $this->error('--delete-old requires --commit');
            return 1;
        }

        $allowed = (array) config('book_import.allowed_categories', []);
        $aliases = (array) config('book_import.category_aliases', []);

        if (empty($allowed)) {
            $this->error('No allowed categories configured in config/book_import.php');
            return 1;
        }

        // Ensure canonical categories exist and build canonical maps.
        $canonical = Category::query()
            ->whereIn('name', $allowed)
            ->get(['id', 'name']);

        if ($canonical->count() !== count($allowed)) {
            $missing = array_values(array_diff($allowed, $canonical->pluck('name')->all()));
            $this->error('Missing canonical categories in DB. Run: php artisan db:seed --class=ExcelCategoriesSeeder');
            if (!empty($missing)) {
                $this->line('Missing: ' . implode(' | ', $missing));
            }
            return 1;
        }

        $canonicalByNormalized = [];
        $canonicalIdByName = [];
        foreach ($canonical as $cat) {
            $canonicalIdByName[$cat->name] = (int)$cat->id;
            $canonicalByNormalized[$this->normalizeForMatch($cat->name)] = $cat->name;
        }

        $allCategories = Category::query()->orderBy('id')->get(['id', 'name']);
        $allowedSet = array_fill_keys($allowed, true);

        $mappings = []; // oldId => canonicalId
        $mappingReasons = []; // oldId => reason string

        foreach ($allCategories as $cat) {
            $id = (int)$cat->id;
            $name = (string)$cat->name;

            // Skip canonical categories
            if (isset($allowedSet[$name])) {
                continue;
            }

            $cleanName = $this->normalizeSpaces($name);
            if ($cleanName === '') {
                continue;
            }

            // 1) Manual aliases (exact name match after space normalization)
            if (array_key_exists($cleanName, $aliases)) {
                $to = $this->normalizeSpaces((string)$aliases[$cleanName]);
                if (!isset($canonicalIdByName[$to])) {
                    $this->warn("Alias target '{$to}' is not in allowed_categories (skipping alias for '{$cleanName}').");
                } else {
                    $mappings[$id] = $canonicalIdByName[$to];
                    $mappingReasons[$id] = "alias: {$cleanName} => {$to}";
                    continue;
                }
            }

            // 2) Auto-match by normalized form (safe: only if exact normalized match)
            $normalized = $this->normalizeForMatch($cleanName);
            if (isset($canonicalByNormalized[$normalized])) {
                $canonicalName = $canonicalByNormalized[$normalized];
                $mappings[$id] = $canonicalIdByName[$canonicalName];
                $mappingReasons[$id] = "auto: {$cleanName} => {$canonicalName}";
            }
        }

        // Pivot usage stats for reporting.
        $notAllowed = $allCategories->filter(function ($c) use ($allowedSet) {
            return !isset($allowedSet[$c->name ?? '']);
        })->values();

        $notAllowedIds = $notAllowed->pluck('id')->map(function ($v) {
            return (int)$v;
        })->all();

        $pivotCounts = [];
        if (!empty($notAllowedIds)) {
            $rows = DB::table('pivot_book_categories')
                ->selectRaw('category_id, COUNT(*) as c')
                ->whereIn('category_id', $notAllowedIds)
                ->groupBy('category_id')
                ->get();

            foreach ($rows as $r) {
                $pivotCounts[(int)$r->category_id] = (int)$r->c;
            }
        }

        $mappedIds = array_keys($mappings);
        $mappedPivotLinks = 0;
        foreach ($mappedIds as $oldId) {
            $mappedPivotLinks += $pivotCounts[$oldId] ?? 0;
        }

        $this->info('Canonical (Excel) categories: ' . count($allowed));
        $this->info('Total categories in DB: ' . $allCategories->count());
        $this->info('Non-canonical categories in DB: ' . $notAllowed->count());
        $this->info('Mappable categories (by alias or safe auto-match): ' . count($mappings));
        $this->info('Pivot links that would be moved: ' . $mappedPivotLinks);

        // Show a short mapping preview
        if (!empty($mappings)) {
            $this->line('--- mapping preview (up to 20) ---');
            $shown = 0;
            foreach ($mappings as $oldId => $newId) {
                $oldName = optional($allCategories->firstWhere('id', $oldId))->name;
                $newName = optional($canonical->firstWhere('id', $newId))->name;
                $reason = $mappingReasons[$oldId] ?? '';
                $count = $pivotCounts[$oldId] ?? 0;
                $this->line("#{$oldId} '{$oldName}' => #{$newId} '{$newName}' (links: {$count}) {$reason}");
                $shown++;
                if ($shown >= 20) {
                    if (count($mappings) > 20) {
                        $this->line('... (more)');
                    }
                    break;
                }
            }
        }

        // Show unmapped categories (top by pivot usage)
        $unmapped = [];
        foreach ($notAllowed as $c) {
            $id = (int)$c->id;
            if (isset($mappings[$id])) {
                continue;
            }
            $unmapped[] = [
                'id' => $id,
                'name' => (string)$c->name,
                'links' => (int)($pivotCounts[$id] ?? 0),
            ];
        }

        usort($unmapped, function ($a, $b) {
            return $b['links'] <=> $a['links'];
        });

        $this->line('--- unmapped categories (top 25 by usage) ---');
        $i = 0;
        foreach ($unmapped as $row) {
            $this->line("#{$row['id']} ({$row['links']}) {$row['name']}");
            $i++;
            if ($i >= 25) {
                break;
            }
        }

        if (!$commit) {
            $this->comment('Dry-run only. Add aliases in config/book_import.php if needed, then re-run with --commit.');
            return 0;
        }

        if (empty($mappings)) {
            $this->warn('No mappings found. Nothing to do.');
            return 0;
        }

        DB::transaction(function () use ($mappings, $deleteOld) {
            foreach ($mappings as $oldId => $newId) {
                DB::table('pivot_book_categories')
                    ->where('category_id', $oldId)
                    ->update(['category_id' => $newId]);
            }

            // Remove duplicate pivot rows for same (book_id, category_id)
            $dups = DB::table('pivot_book_categories')
                ->selectRaw('MIN(id) as keep_id, book_id, category_id, COUNT(*) as c')
                ->groupBy('book_id', 'category_id')
                ->having('c', '>', 1)
                ->get();

            foreach ($dups as $row) {
                DB::table('pivot_book_categories')
                    ->where('book_id', $row->book_id)
                    ->where('category_id', $row->category_id)
                    ->where('id', '!=', $row->keep_id)
                    ->delete();
            }

            if ($deleteOld) {
                $oldIds = array_keys($mappings);
                // Delete only if no remaining pivot links
                foreach ($oldIds as $oldId) {
                    $left = DB::table('pivot_book_categories')->where('category_id', $oldId)->count();
                    if ($left === 0) {
                        Category::where('id', $oldId)->delete();
                    }
                }
            }
        });

        $this->info('Done. Pivot links updated.' . ($deleteOld ? ' Old categories deleted where unused.' : ''));
        return 0;
    }

    private function normalizeSpaces(string $s): string
    {
        $s = preg_replace('/\s+/u', ' ', trim($s));
        return $s ?? '';
    }

    /**
     * Safe normalization for matching:
     * - normalize whitespace
     * - normalize common Arabic letter variants
     * - remove diacritics/tatweel
     * - replace punctuation with spaces
     * - remove leading "ال" from words
     */
    private function normalizeForMatch(string $s): string
    {
        $s = $this->normalizeSpaces($s);
        if ($s === '') {
            return '';
        }

        // Remove tatweel and Arabic diacritics
        $s = preg_replace('/[\x{0640}\x{064B}-\x{065F}\x{0670}]/u', '', $s) ?? $s;

        // Normalize Arabic letter forms
        $s = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $s);
        $s = str_replace(['ى'], 'ي', $s);
        $s = str_replace(['ة'], 'ه', $s);

        // Normalize punctuation/separators
        $s = str_replace(['،', ',', ';', '؛', '/', '\\', '-', '–', '—', '|', '・'], ' ', $s);
        $s = $this->normalizeSpaces($s);

        // Remove leading "ال" from each word (helps: الأديان -> اديان)
        $words = explode(' ', $s);
        $words = array_map(function ($w) {
            $w = trim($w);
            // Handle common prefixes: "ال..." and "وال..." (and similar)
            if (mb_substr($w, 0, 3, 'UTF-8') === 'وال' && mb_strlen($w, 'UTF-8') > 3) {
                return 'و' . mb_substr($w, 3, null, 'UTF-8');
            }
            if (mb_substr($w, 0, 3, 'UTF-8') === 'بال' && mb_strlen($w, 'UTF-8') > 3) {
                return 'ب' . mb_substr($w, 3, null, 'UTF-8');
            }
            if (mb_substr($w, 0, 3, 'UTF-8') === 'كال' && mb_strlen($w, 'UTF-8') > 3) {
                return 'ك' . mb_substr($w, 3, null, 'UTF-8');
            }
            if (mb_substr($w, 0, 3, 'UTF-8') === 'فال' && mb_strlen($w, 'UTF-8') > 3) {
                return 'ف' . mb_substr($w, 3, null, 'UTF-8');
            }
            if (mb_substr($w, 0, 2, 'UTF-8') === 'ال' && mb_strlen($w, 'UTF-8') > 2) {
                return mb_substr($w, 2, null, 'UTF-8');
            }
            return $w;
        }, $words);

        $s = implode(' ', array_values(array_filter($words, function ($w) {
            return $w !== '';
        })));

        return $this->normalizeSpaces($s);
    }
}

