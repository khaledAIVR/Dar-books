<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DedupeCategories extends Command
{
    protected $signature = 'categories:dedupe {--commit : Apply changes (default is dry-run)}';

    protected $description = 'Merge duplicate categories by normalized name and update pivot references.';

    public function handle(): int
    {
        $commit = (bool)$this->option('commit');

        $categories = Category::query()
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get();

        $groups = [];
        foreach ($categories as $cat) {
            $normalized = $this->normalizeName($cat->name);
            if ($normalized === '') {
                // Skip empty names (don’t try to merge these automatically)
                continue;
            }
            $groups[$normalized] = $groups[$normalized] ?? [];
            $groups[$normalized][] = (int)$cat->id;
        }

        $dupGroups = array_filter($groups, function (array $ids) {
            return count($ids) > 1;
        });

        $dupCategoryCount = 0;
        foreach ($dupGroups as $ids) {
            $dupCategoryCount += (count($ids) - 1);
        }

        $this->info('Duplicate groups: ' . count($dupGroups));
        $this->info('Duplicate category rows (to remove): ' . $dupCategoryCount);

        if (!$commit) {
            $this->comment('Dry-run only. Re-run with --commit to apply changes.');
            // Print a small preview
            $preview = 0;
            foreach ($dupGroups as $name => $ids) {
                $this->line("- {$name}: [" . implode(', ', $ids) . ']');
                $preview++;
                if ($preview >= 10) {
                    if (count($dupGroups) > 10) {
                        $this->line('... (more)');
                    }
                    break;
                }
            }
            return 0;
        }

        DB::transaction(function () use ($dupGroups) {
            // 1) Merge category IDs (update pivot table to canonical)
            foreach ($dupGroups as $normalizedName => $ids) {
                $canonicalId = $ids[0]; // smallest id (since we ordered by id)
                $duplicateIds = array_slice($ids, 1);

                // Normalize canonical display name (trim/collapse spaces)
                Category::where('id', $canonicalId)->update(['name' => $normalizedName]);

                DB::table('pivot_book_categories')
                    ->whereIn('category_id', $duplicateIds)
                    ->update(['category_id' => $canonicalId]);

                Category::whereIn('id', $duplicateIds)->delete();
            }

            // 2) Remove duplicate pivot rows for same (book_id, category_id)
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
        });

        $this->info('Done. Categories merged and pivot table cleaned.');

        return 0;
    }

    private function normalizeName($name): string
    {
        $n = trim((string)$name);
        if ($n === '') {
            return '';
        }
        $n = preg_replace('/\s+/u', ' ', $n);
        return $n ?? '';
    }
}

