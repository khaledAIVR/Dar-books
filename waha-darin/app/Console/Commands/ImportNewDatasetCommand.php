<?php

namespace App\Console\Commands;

use App\Services\BookBulkImportService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImportNewDatasetCommand extends Command
{
    protected $signature = 'books:import-new-dataset
        {--excel= : Path to .xlsx (default: new_dataset/x_with_covers.xlsx under base path)}
        {--covers-dir= : Directory containing cover images (default: new_dataset/covers)}
        {--dry-run : Parse and report row/cover stats without writing DB or copying files}';

    protected $description = 'Import books from the new_dataset Excel file, copy covers into public/media/covers, run BookBulkImportService.';

    public function handle(BookBulkImportService $importer): int
    {
        $base = base_path();
        $excel = $this->option('excel') ?: $base.'/new_dataset/x_with_covers.xlsx';
        $coversDir = $this->option('covers-dir') ?: $base.'/new_dataset/covers';

        if (!is_readable($excel)) {
            $this->error("Excel not readable: {$excel}");

            return 1;
        }
        if (!is_dir($coversDir)) {
            $this->error("Covers directory missing: {$coversDir}");

            return 1;
        }

        $uploaded = new UploadedFile(
            $excel,
            basename($excel),
            mime_content_type($excel) ?: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $parsed = $importer->parse($uploaded);
        if (!empty($parsed['warnings'])) {
            foreach ($parsed['warnings'] as $w) {
                $this->warn($w);
            }
        }
        if (empty($parsed['rows'])) {
            $this->error('No data rows parsed from Excel.');

            return 1;
        }

        $rows = [];
        $missingCovers = 0;
        $copiedCovers = 0;
        $destPrefix = 'media/covers';

        if (!$this->option('dry-run')) {
            $dir = public_path($destPrefix);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        foreach ($parsed['rows'] as $raw) {
            $row = $this->normalizeDatasetRow($raw);
            $coverName = isset($raw['cover_filename']) ? trim((string) $raw['cover_filename']) : '';
            if ($coverName === '' && isset($row['image'])) {
                $coverName = trim((string) $row['image']);
            }

            if ($coverName !== '') {
                $src = $coversDir.DIRECTORY_SEPARATOR.$coverName;
                if (!is_readable($src)) {
                    ++$missingCovers;
                    $row['image'] = '';
                } elseif ($this->option('dry-run')) {
                    ++$copiedCovers;
                } else {
                    $rel = $this->storeCoverFile($destPrefix, $src, $coverName, (string) ($row['title'] ?? ''), (string) ($row['isbn'] ?? ''));
                    $row['image'] = $rel;
                    ++$copiedCovers;
                }
            } else {
                $row['image'] = '';
            }

            $rows[] = $row;
        }

        $this->info('Rows: '.count($rows).', covers resolved: '.$copiedCovers.', missing cover files: '.$missingCovers);

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no database import.');

            return 0;
        }

        $result = $importer->import($rows);

        $this->info('Inserted: '.$result['inserted'].', skipped: '.$result['skipped'].', errors: '.count($result['errors']));
        $this->line('Created authors: '.$result['created']['authors'].', publishers: '.$result['created']['publishers'].', categories: '.$result['created']['categories']);

        foreach (array_slice($result['errors'], 0, 20) as $e) {
            $this->error('Row '.($e['row'] ?? '?').': '.($e['message'] ?? ''));
        }
        if (count($result['errors']) > 20) {
            $this->warn('... and '.(count($result['errors']) - 20).' more errors');
        }

        return count($result['errors']) > 0 ? 1 : 0;
    }

    /**
     * Map new_dataset columns to keys expected by BookBulkImportService::import().
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeDatasetRow(array $row): array
    {
        $title = $this->pickMeaningfulText($row, ['title_primary', 'title_alt', 'title', 'name', 'book_title']);
        $author = $this->pickMeaningfulText($row, ['author_primary', 'author_alt', 'author', 'author_name']);
        $publisher = isset($row['publisher']) ? trim((string) $row['publisher']) : '';
        $category = isset($row['category']) ? trim((string) $row['category']) : '';
        if ($category !== '' && strcasecmp($category, 'category') === 0) {
            $category = '';
        }
        $isbn = isset($row['isbn']) ? trim((string) $row['isbn']) : '';

        $out = $row;
        $out['title'] = $title;
        $out['author'] = $author;
        $out['publisher'] = $publisher;
        $out['category'] = $category;
        $out['isbn'] = $isbn;

        if (isset($row['internal_code'])) {
            $out['internal_code'] = $row['internal_code'];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function pickMeaningfulText(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }
            $v = $row[$key];
            if ($v === null) {
                continue;
            }
            if (is_bool($v)) {
                $s = $v ? 'true' : '';
            } else {
                $s = trim((string) $v);
            }
            if ($s === '') {
                continue;
            }
            if (strtolower($s) === 'false') {
                continue;
            }

            return $s;
        }

        return '';
    }

    private function storeCoverFile(string $destPrefix, string $srcPath, string $originalName, string $title, string $isbn): string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg');
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg';

        $base = $isbn !== '' ? preg_replace('/[^0-9X]/i', '', $isbn) : '';
        if ($base === '') {
            $base = Str::slug(Str::limit($title, 80, '')) ?: 'book';
        }
        $base = Str::limit($base, 120, '');

        $name = $base.'.'.$ext;
        $rel = $destPrefix.'/'.$name;
        $abs = public_path($rel);
        $i = 2;
        while (is_file($abs)) {
            $rel = $destPrefix.'/'.$base.'_'.$i.'.'.$ext;
            $abs = public_path($rel);
            ++$i;
        }

        $stream = fopen($srcPath, 'rb');
        if ($stream === false) {
            return '';
        }
        file_put_contents($abs, stream_get_contents($stream));
        fclose($stream);

        return $rel;
    }
}
