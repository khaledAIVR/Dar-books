<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportCoversByIsbn extends Command
{
    /**
     * Export existing local covers into a folder named by ISBN (fallback otherwise).
     *
     * Source: Storage::disk('public')->path($book->image)
     * Output: <out>/{isbn}.{ext} OR {title}__{author}__{publisher}__id{id}.{ext}
     */
    protected $signature = 'covers:export-by-isbn
        {--out= : Output directory (default: storage/app/covers_by_isbn)}
        {--limit=0 : Max books to process (0 = no limit)}
        {--dry-run : Do not copy files, only report}';

    protected $description = 'Export existing book covers into a folder, renamed by ISBN';

    public function handle(): int
    {
        $outDir = (string)($this->option('out') ?: storage_path('app/covers_by_isbn'));
        $limit = (int)($this->option('limit') ?: 0);
        $dryRun = (bool)$this->option('dry-run');

        if (!is_dir($outDir)) {
            if (!mkdir($outDir, 0775, true) && !is_dir($outDir)) {
                $this->error("Unable to create output directory: {$outDir}");
                return 1;
            }
        }

        $reportPath = rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '_export_report.csv';
        $reportHandle = @fopen($reportPath, 'w');
        if (!$reportHandle) {
            $this->error("Unable to write report file: {$reportPath}");
            return 1;
        }

        fputcsv($reportHandle, [
            'book_id',
            'isbn',
            'title',
            'author',
            'publisher',
            'src_image',
            'src_exists',
            'dest_filename',
            'status',
            'note',
        ]);

        $this->info('Exporting covers...');
        $this->line("Source disk: public (storage/app/public)");
        $this->line("Output dir: {$outDir}");
        $this->line("Report: {$reportPath}");
        if ($dryRun) {
            $this->warn('Dry-run enabled: no files will be copied.');
        }

        $processed = 0;
        $exported = 0;
        $skipped = 0;
        $missing = 0;

        $query = Book::query()
            ->with(['author:id,name', 'publisher:id,name'])
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('id');

        $query->chunkById(200, function ($books) use (
            $outDir,
            $limit,
            $dryRun,
            $reportHandle,
            &$processed,
            &$exported,
            &$skipped,
            &$missing
        ) {
            foreach ($books as $book) {
                if ($limit > 0 && $processed >= $limit) {
                    return false; // stop chunking
                }

                $processed++;

                $srcRel = $this->normalizePublicDiskPath((string) $book->image);
                if (!$srcRel || preg_match('/^https?:\/\//i', $srcRel)) {
                    $skipped++;
                    fputcsv($reportHandle, [
                        $book->id,
                        (string) $book->ISBN,
                        (string) $book->title,
                        (string) optional($book->author)->name,
                        (string) optional($book->publisher)->name,
                        (string) $book->image,
                        0,
                        '',
                        'skipped',
                        'image is empty or remote url',
                    ]);
                    continue;
                }

                $srcAbs = Storage::disk('public')->path($srcRel);
                $srcExists = is_file($srcAbs);
                if (!$srcExists) {
                    $missing++;
                    fputcsv($reportHandle, [
                        $book->id,
                        (string) $book->ISBN,
                        (string) $book->title,
                        (string) optional($book->author)->name,
                        (string) optional($book->publisher)->name,
                        (string) $book->image,
                        0,
                        '',
                        'missing',
                        "file not found at {$srcAbs}",
                    ]);
                    continue;
                }

                $ext = $this->guessExtensionFromPath($srcAbs) ?: 'jpg';
                $isbn = $this->normalizeIsbn((string) $book->ISBN);

                $base = $isbn ?: $this->fallbackBaseName(
                    (string) $book->title,
                    (string) optional($book->author)->name,
                    (string) optional($book->publisher)->name,
                    (int) $book->id
                );

                $destFilename = $base . '.' . $ext;
                $destPath = rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $destFilename;

                // Avoid overwriting in case of duplicate ISBN or duplicates after fallback normalization
                if (is_file($destPath)) {
                    $destFilename = $base . '__id' . $book->id . '.' . $ext;
                    $destPath = rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $destFilename;
                }

                $status = 'exported';
                $note = '';

                if (!$dryRun) {
                    if (!@copy($srcAbs, $destPath)) {
                        $status = 'error';
                        $note = 'copy failed';
                        $skipped++;
                    } else {
                        $exported++;
                    }
                } else {
                    $note = 'dry-run';
                }

                fputcsv($reportHandle, [
                    $book->id,
                    $isbn,
                    (string) $book->title,
                    (string) optional($book->author)->name,
                    (string) optional($book->publisher)->name,
                    (string) $book->image,
                    1,
                    $destFilename,
                    $status,
                    $note,
                ]);

                if ($processed % 200 === 0) {
                    $this->line("Processed: {$processed} | Exported: {$exported} | Missing: {$missing} | Skipped: {$skipped}");
                }
            }

            return true;
        });

        fclose($reportHandle);

        $this->line('');
        $this->info('Done.');
        $this->line("Processed: {$processed}");
        $this->line("Exported: {$exported}");
        $this->line("Missing files: {$missing}");
        $this->line("Skipped: {$skipped}");
        $this->line("Report: {$reportPath}");

        return 0;
    }

    private function normalizePublicDiskPath(string $image): string
    {
        $image = trim($image);
        if ($image === '') {
            return '';
        }

        // Common stored values might include "storage/..." or "/storage/..."
        $image = preg_replace('#^/+#', '', $image) ?? $image;
        $image = preg_replace('#^storage/#', '', $image) ?? $image;
        $image = preg_replace('#^public/#', '', $image) ?? $image;

        return trim($image);
    }

    private function normalizeIsbn(string $isbn): string
    {
        $isbn = trim($isbn);
        if ($isbn === '' || $isbn === '0') {
            return '';
        }

        // Keep digits and X only (ISBN-10 can include X)
        $isbn = preg_replace('/[^0-9Xx]+/', '', $isbn) ?? '';
        $isbn = strtoupper($isbn);

        if ($isbn === '' || $isbn === '0') {
            return '';
        }

        return $isbn;
    }

    private function guessExtensionFromPath(string $path): ?string
    {
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if ($ext && in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }
        return null;
    }

    private function fallbackBaseName(string $title, string $author, string $publisher, int $id): string
    {
        $t = $this->filenameSafe($title);
        $a = $this->filenameSafe($author);
        $p = $this->filenameSafe($publisher);

        $parts = array_values(array_filter([$t, $a, $p]));
        $base = implode('__', $parts);
        if ($base === '') {
            $base = 'unknown';
        }

        // Always include id to ensure uniqueness/stability
        $base .= '__id' . $id;

        // Keep filename reasonably short
        if (mb_strlen($base) > 180) {
            $base = mb_substr($base, 0, 180);
        }

        return $base;
    }

    private function filenameSafe(string $s): string
    {
        $s = trim((string) $s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        $s = str_replace(['/', '\\'], ' ', $s);
        // allow unicode letters/numbers, replace the rest with "-"
        $s = preg_replace('/[^\pL\pN\.\-\s_]+/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/u', '-', $s) ?? $s;
        $s = trim($s, '-');

        return $s;
    }
}

