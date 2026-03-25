<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportBookCoversByTitle extends Command
{
    protected $signature = 'books:export-covers-by-title
        {--disk=public : Storage disk where current cover images live}
        {--dest=book-covers-by-title : Destination folder within the disk}
        {--mapping=_mapping.tsv : Mapping file name inside destination folder}
        {--failed=_failed.tsv : Failed/missing entries file name inside destination folder}
        {--dry-run : Do not copy anything, just report and write mapping/failed files}';

    protected $description = 'Copy all book cover images into a folder, naming files from book title, and write a reverse mapping file.';

    public function handle(): int
    {
        $diskName = (string)$this->option('disk');
        $destDir = trim((string)$this->option('dest'), "/ \t\n\r\0\x0B");
        $mappingName = (string)$this->option('mapping');
        $failedName = (string)$this->option('failed');
        $dryRun = (bool)$this->option('dry-run');

        if ($destDir === '') {
            $this->error('--dest cannot be empty.');
            return 1;
        }

        $driver = (string)config("filesystems.disks.{$diskName}.driver", '');
        if (strtolower($driver) !== 'local') {
            $this->error("Disk '{$diskName}' driver is '{$driver}'. This command currently supports only local disks (e.g. 'public').");
            return 1;
        }

        $disk = Storage::disk($diskName);

        if (!$disk->exists($destDir)) {
            $disk->makeDirectory($destDir);
        }

        $mappingPath = rtrim($destDir, '/') . '/' . ltrim($mappingName, '/');
        $failedPath = rtrim($destDir, '/') . '/' . ltrim($failedName, '/');

        // We write the TSV using direct filesystem path (supported by local driver disks).
        $mappingFsPath = method_exists($disk, 'path') ? $disk->path($mappingPath) : null;
        $failedFsPath = method_exists($disk, 'path') ? $disk->path($failedPath) : null;

        if (!$mappingFsPath || !$failedFsPath) {
            $this->error("Disk '{$diskName}' does not support writing mapping files via path(). Use a local disk (default: public).");
            return 1;
        }

        $mappingHandle = fopen($mappingFsPath, 'w');
        $failedHandle = fopen($failedFsPath, 'w');
        if (!$mappingHandle || !$failedHandle) {
            $this->error('Unable to open mapping/failed file for writing.');
            return 1;
        }

        // TSV headers
        fwrite($mappingHandle, implode("\t", [
            'new_rel_path',
            'book_id',
            'book_title',
            'src_rel_path',
        ]) . "\n");
        fwrite($failedHandle, implode("\t", [
            'book_id',
            'book_title',
            'src_image_value',
            'reason',
        ]) . "\n");

        $query = Book::query()
            ->select(['id', 'title', 'image'])
            ->orderBy('id');

        $total = (clone $query)->count();
        $this->info("Found {$total} books.");
        if ($dryRun) {
            $this->warn('Dry-run enabled: no files will be copied.');
        }

        $copied = 0;
        $skipped = 0;
        $failed = 0;

        $query->chunkById(200, function ($books) use (
            $disk,
            $destDir,
            $dryRun,
            &$copied,
            &$skipped,
            &$failed,
            $mappingHandle,
            $failedHandle
        ) {
            foreach ($books as $book) {
                $bookId = (int)$book->id;
                $title = (string)($book->title ?? '');
                $image = trim((string)($book->image ?? ''));

                if ($image === '') {
                    $skipped++;
                    continue;
                }

                $srcRelPath = $this->normalizeImagePathToDiskRelative($image);
                if ($srcRelPath === null) {
                    $failed++;
                    fwrite($failedHandle, implode("\t", [
                        (string)$bookId,
                        $this->tsvEscape($title),
                        $this->tsvEscape($image),
                        'unsupported_image_path',
                    ]) . "\n");
                    continue;
                }

                if (!$disk->exists($srcRelPath)) {
                    $failed++;
                    fwrite($failedHandle, implode("\t", [
                        (string)$bookId,
                        $this->tsvEscape($title),
                        $this->tsvEscape($image),
                        'source_not_found',
                    ]) . "\n");
                    continue;
                }

                $ext = strtolower((string)pathinfo($srcRelPath, PATHINFO_EXTENSION));
                if ($ext === '') {
                    $ext = 'jpg';
                }

                $base = $this->sanitizeTitleForFilename($title);
                if ($base === '') {
                    $base = 'book';
                }

                $candidate = $base . '.' . $ext;
                $destRelPath = rtrim($destDir, '/') . '/' . $candidate;

                // Avoid collisions by appending the book id when needed.
                if ($disk->exists($destRelPath)) {
                    $suffixBase = '-' . $bookId;
                    $n = 1;
                    do {
                        $suffix = $suffixBase . ($n === 1 ? '' : '-' . $n);
                        $baseLimited = $this->limitBaseLengthForSuffixAndExt($base, $suffix, $ext);
                        $candidate = $baseLimited . $suffix . '.' . $ext;
                        $destRelPath = rtrim($destDir, '/') . '/' . $candidate;
                        $n++;
                    } while ($disk->exists($destRelPath));
                }

                if (!$dryRun) {
                    $ok = $disk->copy($srcRelPath, $destRelPath);
                    if (!$ok) {
                        $failed++;
                        fwrite($failedHandle, implode("\t", [
                            (string)$bookId,
                            $this->tsvEscape($title),
                            $this->tsvEscape($image),
                            'copy_failed',
                        ]) . "\n");
                        continue;
                    }
                }

                $copied++;
                fwrite($mappingHandle, implode("\t", [
                    $this->tsvEscape($destRelPath),
                    (string)$bookId,
                    $this->tsvEscape($title),
                    $this->tsvEscape($srcRelPath),
                ]) . "\n");
            }
        });

        fclose($mappingHandle);
        fclose($failedHandle);

        $this->info("Done. Copied: {$copied}, skipped(no image): {$skipped}, failed: {$failed}.");
        $this->line("Mapping file: {$mappingPath}");
        $this->line("Failed file:  {$failedPath}");

        return 0;
    }

    /**
     * Normalize stored DB value into a path relative to the given disk root.
     * Returns null if it looks like a remote URL or something we can't map to disk.
     */
    private function normalizeImagePathToDiskRelative(string $image): ?string
    {
        $v = trim($image);
        if ($v === '') {
            return null;
        }

        // If someone stored a full URL, try to map "/storage/..." to the public disk path.
        if (Str::startsWith($v, ['http://', 'https://'])) {
            $path = (string)(parse_url($v, PHP_URL_PATH) ?? '');
            if ($path !== '' && Str::startsWith($path, '/storage/')) {
                $v = $path; // continue normalization below
            } else {
                // Remote URL (we don't download here).
                return null;
            }
        }

        // Strip known prefixes (common when someone stored a URL-like value).
        $v = preg_replace('#^/+storage/+?#', '', $v) ?? $v; // "/storage/books/x.jpg" => "books/x.jpg"
        $v = preg_replace('#^storage/+?#', '', $v) ?? $v;   // "storage/books/x.jpg" => "books/x.jpg"
        $v = preg_replace('#^public/+?#', '', $v) ?? $v;    // "public/books/x.jpg" => "books/x.jpg"

        $v = ltrim($v, '/');
        if ($v === '' || Str::contains($v, ['..', "\0"])) {
            return null;
        }

        return $v;
    }

    /**
     * Create a filesystem-friendly base filename from the title.
     * We keep unicode letters/numbers, but remove path separators and reserved characters.
     */
    private function sanitizeTitleForFilename(string $title): string
    {
        $t = trim($title);
        if ($t === '') {
            return '';
        }

        // Normalize whitespace.
        $t = preg_replace('/\s+/u', ' ', $t) ?? $t;

        // First try an ASCII-ish slug; if it becomes empty (Arabic, etc), fall back to unicode-safe.
        $slug = Str::slug($t, '-');
        if ($slug !== '') {
            return $this->limitUtf8($slug, 160);
        }

        // Keep letters/numbers, spaces, dashes, underscores. Replace spaces with dashes.
        $t = str_replace(['/', '\\'], ' ', $t);
        $t = preg_replace('/[^\p{L}\p{N}\s\-_]+/u', '', $t) ?? $t;
        $t = preg_replace('/\s+/u', '-', $t) ?? $t;
        $t = preg_replace('/-+/u', '-', $t) ?? $t;
        $t = trim($t, '-_. ');

        return $this->limitUtf8($t, 160);
    }

    private function limitUtf8(string $s, int $maxChars): string
    {
        if ($maxChars <= 0) {
            return '';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($s, 'UTF-8') <= $maxChars) {
                return $s;
            }
            return mb_substr($s, 0, $maxChars, 'UTF-8');
        }
        return substr($s, 0, $maxChars);
    }

    private function limitBaseLengthForSuffixAndExt(string $base, string $suffix, string $ext): string
    {
        // Keep filenames comfortably under common filesystem limits.
        // <base><suffix>.<ext>
        $max = 200;
        $fixed = strlen($suffix) + 1 + strlen($ext); // ".ext" counts as 1 + ext
        $allowed = max(1, $max - $fixed);
        return $this->limitUtf8($base, $allowed);
    }

    private function tsvEscape(string $v): string
    {
        // Make it single-line TSV.
        $v = str_replace(["\t", "\r", "\n"], ['\\t', '\\r', '\\n'], $v);
        return $v;
    }
}

