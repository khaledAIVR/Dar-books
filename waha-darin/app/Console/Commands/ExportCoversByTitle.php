<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportCoversByTitle extends Command
{
    /**
     * Export existing local covers into a folder named by normalized title.
     *
     * Output filenames:
     *   <normalized_title_key>.<ext>
     * If collision, we append: <normalized_title_key>__id<ID>.<ext>
     */
    protected $signature = 'covers:export-by-title
        {--out= : Output directory (default: storage/app/covers_by_title)}
        {--limit=0 : Max books to process (0 = no limit)}
        {--dry-run : Do not copy files, only report}';

    protected $description = 'Export existing book covers into a folder, renamed by normalized title';

    public function handle(): int
    {
        $outDir = (string)($this->option('out') ?: storage_path('app/covers_by_title'));
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
            'title_key',
            'author',
            'publisher',
            'src_image',
            'src_exists',
            'dest_filename',
            'status',
            'note',
        ]);

        $this->info('Exporting covers (by title)...');
        $this->line('Source disk: public (storage/app/public)');
        $this->line("Output dir: {$outDir}");
        $this->line("Report: {$reportPath}");
        if ($dryRun) {
            $this->warn('Dry-run enabled: no files will be copied.');
        }

        $processed = 0;
        $exported = 0;
        $skipped = 0;
        $missing = 0;
        $collisions = 0;

        $query = Book::query()
            ->with(['author:id,name', 'publisher:id,name'])
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->orderBy('id');

        $query->chunkById(200, function ($books) use (
            $outDir,
            $limit,
            $dryRun,
            $reportHandle,
            &$processed,
            &$exported,
            &$skipped,
            &$missing,
            &$collisions
        ) {
            foreach ($books as $book) {
                if ($limit > 0 && $processed >= $limit) {
                    return false;
                }

                $processed++;

                $srcRel = $this->normalizePublicDiskPath((string) $book->image);
                if (!$srcRel || preg_match('/^https?:\/\//i', $srcRel)) {
                    $skipped++;
                    fputcsv($reportHandle, [
                        $book->id,
                        (string) $book->ISBN,
                        (string) $book->title,
                        '',
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
                        '',
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
                $titleKey = $this->normTitleKey((string) $book->title);
                $base = $this->filenameSafeSlug($titleKey ?: 'untitled');
                if ($base === '') {
                    $base = 'untitled';
                }

                $destFilename = $base . '.' . $ext;
                $destPath = rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $destFilename;
                if (is_file($destPath)) {
                    $collisions++;
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
                    $this->normalizeIsbn((string) $book->ISBN),
                    (string) $book->title,
                    $titleKey,
                    (string) optional($book->author)->name,
                    (string) optional($book->publisher)->name,
                    (string) $book->image,
                    1,
                    $destFilename,
                    $status,
                    $note,
                ]);

                if ($processed % 200 === 0) {
                    $this->line("Processed: {$processed} | Exported: {$exported} | Missing: {$missing} | Skipped: {$skipped} | Collisions: {$collisions}");
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
        $this->line("Collisions: {$collisions}");
        $this->line("Report: {$reportPath}");

        return 0;
    }

    private function normalizePublicDiskPath(string $image): string
    {
        $image = trim($image);
        if ($image === '') {
            return '';
        }

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

    /**
     * Normalize Arabic title for matching.
     * Main requested rules:
     *   ا أ إ آ -> ا
     *   ي ى ئ -> ي
     */
    private function normTitleKey(string $s): string
    {
        $s = trim((string) $s);
        if ($s === '') {
            return '';
        }

        // Collapse whitespace
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        // Arabic unification
        $map = [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ٱ' => 'ا',
            'ى' => 'ي',
            'ئ' => 'ي',
        ];
        $s = strtr($s, $map);

        // Remove diacritics + tatweel
        $s = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]+/u', '', $s) ?? $s;

        // Remove punctuation/symbols (keep letters, numbers, spaces)
        $s = preg_replace('/[^\pL\pN\s]+/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        $s = trim($s);

        // Casefold-ish (mostly for latin)
        if (function_exists('mb_strtolower')) {
            $s = mb_strtolower($s, 'UTF-8');
        } else {
            $s = strtolower($s);
        }

        return $s;
    }

    private function filenameSafeSlug(string $s): string
    {
        $s = trim((string) $s);
        if ($s === '') {
            return '';
        }
        $s = str_replace(['/', '\\'], ' ', $s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        // allow unicode letters/numbers, replace the rest with spaces
        $s = preg_replace('/[^\pL\pN\.\-\s_]+/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/u', '-', $s) ?? $s;
        $s = trim($s, '-');

        if (mb_strlen($s) > 140) {
            $s = mb_substr($s, 0, 140);
            $s = rtrim($s, '-');
        }
        return $s;
    }
}

