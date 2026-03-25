<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookBulkImportService
{
    /**
     * Parse an uploaded CSV/XLSX file into normalized rows.
     *
     * @return array{rows: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function parse(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: '');

        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->parseCsv($file);
        }

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            return $this->parseSpreadsheet($file);
        }

        return [
            'rows' => [],
            'warnings' => ["Unsupported file type: .{$ext}"],
        ];
    }

    /**
     * Import parsed rows into DB.
     *
     * Expected normalized keys (case-insensitive): title, author, publisher, category.
     * Optional: price, isbn, year, description, internal_code, available, image.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *   inserted:int,
     *   skipped:int,
     *   skipped_rows: array<int, array<string, mixed>>,
     *   errors: array<int, array{row:int, message:string}>,
     *   created: array{authors:int, publishers:int, categories:int}
     * }
     */
    public function import(array $rows): array
    {
        $result = [
            'inserted' => 0,
            'skipped' => 0,
            'skipped_rows' => [],
            'errors' => [],
            'created' => ['authors' => 0, 'publishers' => 0, 'categories' => 0],
        ];

        DB::transaction(function () use (&$result, $rows) {
            foreach ($rows as $i => $rawRow) {
                // We keep the original row index for error reporting (1-based, plus header row already removed).
                $rowNumber = (int)($rawRow['__row'] ?? ($i + 2));

                try {
                    $row = $this->normalizeRow($rawRow);

                    $title = trim((string)($row['title'] ?? ''));
                    $authorName = trim((string)($row['author'] ?? ''));
                    if ($authorName === '') {
                        $authorName = trim((string) config('book_import.default_author', ''));
                    }
                    $publisherName = trim((string)($row['publisher'] ?? ''));
                    if ($publisherName === '') {
                        $publisherName = trim((string) config('book_import.default_publisher', ''));
                    }
                    $categoryRaw = trim((string)($row['category'] ?? ''));
                    if ($categoryRaw === '') {
                        $categoryRaw = trim((string) config('book_import.default_category', ''));
                    }

                    if ($title === '') {
                        $result['errors'][] = ['row' => $rowNumber, 'message' => 'Missing Title/Name'];
                        continue;
                    }
                    if ($authorName === '') {
                        $result['errors'][] = ['row' => $rowNumber, 'message' => 'Missing Author'];
                        continue;
                    }
                    if ($publisherName === '') {
                        $result['errors'][] = ['row' => $rowNumber, 'message' => 'Missing Publisher'];
                        continue;
                    }
                    if (trim($categoryRaw) === '') {
                        $result['errors'][] = ['row' => $rowNumber, 'message' => 'Missing Category'];
                        continue;
                    }

                    // Skip duplicates (match existing behavior in BookController)
                    $existing = Book::select('id')->where('title', $title)->first();
                    if ($existing) {
                        $result['skipped']++;
                        $result['skipped_rows'][] = array_merge(
                            ['row' => $rowNumber, 'reason' => 'Duplicate title', 'existing_book_id' => (int)$existing->id],
                            $row
                        );
                        continue;
                    }

                    $authorId = $this->findOrCreateAuthorId($authorName, $result);
                    $publisherId = $this->findOrCreatePublisherId($publisherName, $result);

                    $book = new Book();
                    $book->title = $title;
                    $book->slug = $this->generateUniqueSlug($title);

                    $book->author_id = $authorId;
                    $book->publisher_id = $publisherId;

                    // Defaults for required DB columns
                    $book->price = $this->toPositiveInt($row['price'] ?? 0);
                    $book->ISBN = (string)($row['isbn'] ?? '');
                    $book->year = $this->toPositiveInt($row['year'] ?? 0);
                    $book->Available = $this->toBool($row['available'] ?? true);
                    $book->description = (string)($row['description'] ?? '-');

                    // Important: `books.image` is NOT nullable in migrations.
                    // Keep it as empty string to satisfy DB, while UI still uses default cover in accessor.
                    $book->image = (string)($row['image'] ?? '');

                    if (array_key_exists('internal_code', $row)) {
                        $book->internal_code = $row['internal_code'];
                    }

                    $book->save();

                    $categories = $this->findOrCreateCategoryIds($categoryRaw, $result);
                    if (!empty($categories)) {
                        $book->categories()->sync($categories);
                    }

                    $result['inserted']++;
                } catch (\Throwable $e) {
                    $result['errors'][] = ['row' => $rowNumber, 'message' => $e->getMessage()];
                }
            }
        });

        return $result;
    }

    private function parseCsv(UploadedFile $file): array
    {
        $warnings = [];
        $rows = [];

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return ['rows' => [], 'warnings' => ['Unable to read uploaded CSV']];
        }

        $headers = null;
        $rowIndex = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $rowIndex++;

            if ($rowIndex === 1) {
                $headers = array_map(function ($h) {
                    return $this->normalizeHeader($h);
                }, $data);
                continue;
            }

            if (!$headers) {
                $warnings[] = 'CSV is missing a header row.';
                break;
            }

            $assoc = [];
            foreach ($headers as $idx => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = $data[$idx] ?? null;
            }
            $assoc['__row'] = $rowIndex;
            $rows[] = $assoc;
        }

        fclose($handle);

        return ['rows' => $rows, 'warnings' => $warnings];
    }

    private function parseSpreadsheet(UploadedFile $file): array
    {
        $warnings = [];
        $rows = [];

        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return [
                'rows' => [],
                'warnings' => ['Excel import requires PhpSpreadsheet dependency (phpoffice/phpspreadsheet).'],
            ];
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $matrix = $sheet->toArray(null, true, true, true);

        if (empty($matrix)) {
            return ['rows' => [], 'warnings' => ['Excel sheet is empty.']];
        }

        $headers = null;
        $headerRowNumber = null;

        foreach ($matrix as $r => $columns) {
            // Find the first non-empty row to treat as header
            $hasAny = false;
            foreach ($columns as $val) {
                if (trim((string)$val) !== '') {
                    $hasAny = true;
                    break;
                }
            }
            if (!$hasAny) {
                continue;
            }

            $headers = [];
            foreach ($columns as $colLetter => $val) {
                $headers[$colLetter] = $this->normalizeHeader($val);
            }
            $headerRowNumber = $r;
            break;
        }

        if (!$headers || !$headerRowNumber) {
            return ['rows' => [], 'warnings' => ['Excel is missing a header row.']];
        }

        foreach ($matrix as $r => $columns) {
            if ($r <= $headerRowNumber) {
                continue;
            }

            $assoc = [];
            $hasAny = false;
            foreach ($columns as $colLetter => $val) {
                $key = $headers[$colLetter] ?? '';
                if ($key === '') {
                    continue;
                }
                $cell = is_string($val) ? trim($val) : $val;
                if ($cell !== null && $cell !== '') {
                    $hasAny = true;
                }
                $assoc[$key] = $cell;
            }

            if (!$hasAny) {
                continue;
            }

            $assoc['__row'] = $r;
            $rows[] = $assoc;
        }

        return ['rows' => $rows, 'warnings' => $warnings];
    }

    /**
     * Normalize a row's keys to canonical keys.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $mapped = [];

        foreach ($row as $key => $value) {
            if ($key === '__row') {
                $mapped['__row'] = $value;
                continue;
            }
            $k = $this->normalizeHeader($key);
            if ($k !== '') {
                $mapped[$k] = $value;
            }
        }

        // Map common synonyms to canonical keys
        $syn = function (array $candidates) use ($mapped) {
            foreach ($candidates as $c) {
                if (array_key_exists($c, $mapped)) {
                    return $mapped[$c];
                }
            }
            return null;
        };

        $out = $mapped;
        $out['title'] = $out['title'] ?? $syn(['name', 'book', 'book_title', 'bookname']);
        $out['author'] = $out['author'] ?? $syn(['writer', 'author_name']);
        $out['publisher'] = $out['publisher'] ?? $syn(['publishing_house', 'publisher_name']);
        $out['category'] = $out['category'] ?? $syn(['categories', 'genre', 'tag', 'tags']);
        $out['isbn'] = $out['isbn'] ?? $syn(['ISBN']);
        $out['year'] = $out['year'] ?? $syn(['publication_year']);
        $out['price'] = $out['price'] ?? $syn(['cost']);
        $out['description'] = $out['description'] ?? $syn(['desc']);
        $out['internal_code'] = $out['internal_code'] ?? $syn(['code', 'internalcode']);
        $out['available'] = $out['available'] ?? $syn(['is_available']);
        $out['image'] = $out['image'] ?? $syn(['image_url', 'cover', 'cover_url']);

        return $out;
    }

    private function normalizeHeader($header): string
    {
        $h = trim((string)$header);
        if ($h === '') {
            return '';
        }
        $h = strtolower($h);
        $h = preg_replace('/\s+/', '_', $h);
        $h = preg_replace('/[^a-z0-9_]/', '', $h);
        return $h ?: '';
    }

    private function findOrCreateAuthorId(string $name, array &$result): int
    {
        $author = Author::where('name', $name)->first();
        if ($author) {
            return (int)$author->id;
        }

        $author = new Author();
        $author->name = $name;
        $author->slug = str_slug($name, '-');
        $author->save();
        $result['created']['authors']++;

        return (int)$author->id;
    }

    private function findOrCreatePublisherId(string $name, array &$result): int
    {
        $publisher = Publisher::where('name', $name)->first();
        if ($publisher) {
            return (int)$publisher->id;
        }

        $publisher = new Publisher();
        $publisher->name = $name;
        $publisher->slug = str_slug($name, '-');
        $publisher->save();
        $result['created']['publishers']++;

        return (int)$publisher->id;
    }

    /**
     * @return array<int, int>
     */
    private function findOrCreateCategoryIds(string $categoriesRaw, array &$result): array
    {
        $strict = (bool) config('book_import.strict_categories', false);
        $allowed = (array) config('book_import.allowed_categories', []);
        $allowedMap = $this->buildAllowedCategoryMap($allowed);

        $parts = preg_split('/[|,;]+/', $categoriesRaw) ?: [];
        $parts = array_values(array_filter(array_map(function ($v) {
            $v = preg_replace('/\s+/u', ' ', trim((string)$v));
            return $v ?? '';
        }, $parts), function ($v) {
            return $v !== '';
        }));

        $ids = [];
        foreach ($parts as $name) {
            $name = $this->resolveCategoryAlias($name);
            if ($strict) {
                $normalized = $this->normalizeCategoryName($name);
                if (!isset($allowedMap[$normalized])) {
                    $name = $this->resolveCategoryClosestOrOthers($name, $allowed, $allowedMap);
                    $normalized = $this->normalizeCategoryName($name);
                }
                if (!isset($allowedMap[$normalized])) {
                    throw new \RuntimeException("Unknown category: {$name}");
                }
                $name = $allowedMap[$normalized]; // canonical name from config
            }

            $category = Category::where('name', $name)->first();
            if (!$category) {
                if ($strict && empty($allowed)) {
                    throw new \RuntimeException("Category list is empty while strict mode is enabled.");
                }
                $category = new Category();
                $category->name = $name;
                $category->slug = str_slug($name, '-');
                $category->save();
                $result['created']['categories']++;
            }
            $ids[] = (int)$category->id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<int, string> $allowed
     * @return array<string, string> normalized => canonical
     */
    private function buildAllowedCategoryMap(array $allowed): array
    {
        $map = [];
        foreach ($allowed as $name) {
            $name = preg_replace('/\s+/u', ' ', trim((string) $name));
            if (!$name) {
                continue;
            }
            $map[$this->normalizeCategoryName($name)] = $name;
        }
        return $map;
    }

    /**
     * Map alternate spellings (e.g. different Arabic hamza) via category_aliases before strict checks.
     */
    private function resolveCategoryAlias(string $name): string
    {
        $aliases = (array) config('book_import.category_aliases', []);
        $trimmed = preg_replace('/\s+/u', ' ', trim($name));
        if ($trimmed === '') {
            return $name;
        }
        if (isset($aliases[$trimmed])) {
            return (string) $aliases[$trimmed];
        }
        $norm = $this->normalizeCategoryName($trimmed);
        foreach ($aliases as $from => $to) {
            if ($this->normalizeCategoryName((string) $from) === $norm) {
                return (string) $to;
            }
        }

        return $trimmed;
    }

    /**
     * Map a non-empty label to the closest main category, or to the configured "Others" bucket.
     *
     * @param  array<int, string>  $allowed
     * @param  array<string, string>  $allowedMap
     */
    private function resolveCategoryClosestOrOthers(string $name, array $allowed, array $allowedMap): string
    {
        $trimmed = preg_replace('/\s+/u', ' ', trim($name));
        $normalizedInput = $this->normalizeCategoryName($trimmed);
        if ($normalizedInput === '') {
            return (string) config('book_import.fallback_category_others', 'أخرى');
        }
        if (isset($allowedMap[$normalizedInput])) {
            return $allowedMap[$normalizedInput];
        }

        $others = preg_replace('/\s+/u', ' ', trim((string) config('book_import.fallback_category_others', 'أخرى')));
        $othersNorm = $this->normalizeCategoryName($others);

        $minScore = (float) config('book_import.category_closest_min_score', 0.28);
        $bestCanonical = null;
        $bestScore = -1.0;

        foreach ($allowed as $canonical) {
            $canonNorm = $this->normalizeCategoryName($canonical);
            if ($canonNorm === $othersNorm) {
                continue;
            }
            $score = $this->categorySimilarityScore($normalizedInput, $canonNorm);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCanonical = $canonical;
            }
        }

        if ($bestCanonical !== null && $bestScore >= $minScore) {
            return $bestCanonical;
        }

        return isset($allowedMap[$othersNorm]) ? $allowedMap[$othersNorm] : $others;
    }

    /**
     * Similarity in 0..1 using substring containment (Arabic-friendly) and similar_text.
     */
    private function categorySimilarityScore(string $normA, string $normB): float
    {
        if ($normA === '' || $normB === '') {
            return 0.0;
        }
        if ($normA === $normB) {
            return 1.0;
        }

        $lenA = mb_strlen($normA);
        $lenB = mb_strlen($normB);
        if ($lenA >= 2 && $lenB >= 2) {
            if (mb_strpos($normB, $normA) !== false || mb_strpos($normA, $normB) !== false) {
                $min = (float) min($lenA, $lenB);
                $max = (float) max($lenA, $lenB);

                return 0.82 + 0.18 * ($min / max($max, 1.0));
            }
        }

        similar_text($normA, $normB, $pct);

        return (float) $pct / 100.0;
    }

    private function normalizeCategoryName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name));
        $name = $name ?? '';
        $name = str_replace(['،', '؛'], [',', ';'], $name);
        // Unify common Arabic alif/hamza forms so allowlist matches sheet variants.
        $name = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $name);

        return Str::lower($name);
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = str_slug($title);
        $slug = $base;
        $i = 2;

        while (Book::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function toPositiveInt($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            $n = (int)$value;
            return $n < 0 ? 0 : $n;
        }
        $n = (int)preg_replace('/[^0-9]/', '', (string)$value);
        return $n < 0 ? 0 : $n;
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $v = strtolower(trim((string)$value));
        if ($v === '') {
            return true;
        }

        return in_array($v, ['1', 'true', 'yes', 'y', 'available'], true);
    }
}

