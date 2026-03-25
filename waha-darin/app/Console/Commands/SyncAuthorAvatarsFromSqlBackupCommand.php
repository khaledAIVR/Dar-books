<?php

namespace App\Console\Commands;

use App\Models\Author;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncAuthorAvatarsFromSqlBackupCommand extends Command
{
    protected $signature = 'authors:sync-avatars-from-backup
        {--file= : Path to mysqldump .sql containing INSERT INTO `authors`}
        {--dry-run : Show counts without updating}
        {--skip-missing-files : Only assign avatar if the file exists on the public disk}';

    protected $description = 'Restore author avatar paths from a SQL backup by matching author names (e.g. after re-importing books).';

    public function handle(): int
    {
        $path = $this->option('file');
        if (!$path || !is_readable($path)) {
            $path = $this->guessBackupPath();
        }
        if (!$path || !is_readable($path)) {
            $this->error('Provide a readable --file=path/to/backup.sql (e.g. storage/app/backups/darin2_pre_wipe_*.sql)');

            return 1;
        }

        $this->info("Reading: {$path}");
        $sql = file_get_contents($path);
        if ($sql === false) {
            $this->error('Could not read file.');

            return 1;
        }

        $map = $this->parseAuthorsFromDump($sql);
        if ($map === []) {
            $this->error('No author avatars found in this dump (missing INSERT, wrong column order, or all avatar values NULL).');
            $this->line('Tip: post-import pipeline backups often have NULL avatars; use a pre-wipe/full dump that still contains paths like authors/September2023/….jpg, or pass --file=storage/app/backups/darin2_pre_wipe_*.sql');

            return 1;
        }

        $this->info('Parsed '.count($map).' author name keys with non-empty avatar from backup.');

        $disk = Storage::disk('public');
        $skipMissing = (bool) $this->option('skip-missing-files');
        $dry = (bool) $this->option('dry-run');

        $updated = 0;
        $skippedHasAvatar = 0;
        $skippedNoMatch = 0;
        $skippedMissingFile = 0;

        Author::query()->orderBy('id')->chunk(200, function ($authors) use ($map, $disk, $skipMissing, $dry, &$updated, &$skippedHasAvatar, &$skippedNoMatch, &$skippedMissingFile) {
            foreach ($authors as $author) {
                if ($author->avatar !== null && trim((string) $author->avatar) !== '') {
                    $skippedHasAvatar++;

                    continue;
                }
                $key = $this->normalizeAuthorName((string) $author->name);
                if ($key === '' || !isset($map[$key])) {
                    $skippedNoMatch++;

                    continue;
                }
                $relPath = $map[$key];
                if ($skipMissing && !$disk->exists($relPath)) {
                    $skippedMissingFile++;

                    continue;
                }
                if (!$dry) {
                    $author->avatar = $relPath;
                    $author->save();
                }
                $updated++;
            }
        });

        $this->table(
            ['Metric', 'Count'],
            [
                ['Updated (empty avatar → backup path)', $updated],
                ['Skipped (already had avatar)', $skippedHasAvatar],
                ['Skipped (no name match in backup)', $skippedNoMatch],
                ['Skipped (missing file on disk)', $skippedMissingFile],
            ]
        );

        if ($dry) {
            $this->warn('Dry run — no database changes.');
        }

        return 0;
    }

    private function guessBackupPath(): ?string
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) {
            return null;
        }
        $files = glob($dir.'/*.sql') ?: [];
        if ($files === []) {
            return null;
        }
        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        foreach ($files as $path) {
            if ($this->sqlDumpLikelyHasAuthorAvatars($path)) {
                return $path;
            }
        }

        return $files[0];
    }

    /**
     * Pipeline/post-import dumps often re-insert authors with NULL avatars; prefer dumps that still store paths.
     */
    private function sqlDumpLikelyHasAuthorAvatars(string $path): bool
    {
        $h = fopen($path, 'rb');
        if ($h === false) {
            return false;
        }
        $chunk = fread($h, 6 * 1024 * 1024);
        fclose($h);
        if ($chunk === false || $chunk === '') {
            return false;
        }

        return (bool) preg_match('/\(\d+,\'authors\//', $chunk);
    }

    /**
     * @return array<string, string> normalized_name => relative public disk path
     */
    private function parseAuthorsFromDump(string $sql): array
    {
        $rows = $this->extractAuthorInsertRows($sql);
        if ($rows === []) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $row = trim($row);
            $fields = $this->splitSqlRowFields($row);
            if (count($fields) < 3) {
                continue;
            }
            $avatarRaw = $this->decodeSqlField($fields[1]);
            $nameRaw = $this->decodeSqlField($fields[2]);
            if ($nameRaw === null || $nameRaw === '') {
                continue;
            }
            if ($avatarRaw === null || $avatarRaw === '') {
                continue;
            }
            $key = $this->normalizeAuthorName($nameRaw);
            if ($key === '') {
                continue;
            }
            $map[$key] = $avatarRaw;
        }

        return $map;
    }

    /**
     * mysqldump: INSERT INTO `authors` VALUES (row1),(row2),...;
     * Each row is the comma-separated field list inside one pair of parentheses (quote-aware).
     *
     * @return array<int, string>
     */
    private function extractAuthorInsertRows(string $sql): array
    {
        $needle = 'INSERT INTO `authors` VALUES';
        $pos = strpos($sql, $needle);
        if ($pos === false) {
            return [];
        }

        $i = strpos($sql, '(', $pos);
        if ($i === false) {
            return [];
        }

        $len = strlen($sql);
        $rows = [];

        while ($i < $len) {
            while ($i < $len && ctype_space($sql[$i])) {
                $i++;
            }
            if ($i >= $len || $sql[$i] !== '(') {
                break;
            }

            $tupleStart = $i + 1;
            $depth = 1;
            $inString = false;
            $p = $i + 1;

            while ($p < $len && $depth > 0) {
                $c = $sql[$p];
                if ($c === "'") {
                    if ($inString && $p + 1 < $len && $sql[$p + 1] === "'") {
                        $p += 2;

                        continue;
                    }
                    $inString = ! $inString;
                    $p++;

                    continue;
                }
                if (! $inString) {
                    if ($c === '(') {
                        $depth++;
                    } elseif ($c === ')') {
                        $depth--;
                    }
                }
                $p++;
            }

            if ($depth !== 0) {
                break;
            }

            $closeParen = $p - 1;
            $rows[] = substr($sql, $tupleStart, $closeParen - $tupleStart);

            $i = $p;
            while ($i < $len && ctype_space($sql[$i])) {
                $i++;
            }
            if ($i < $len && $sql[$i] === ',') {
                $i++;

                continue;
            }

            break;
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function splitSqlRowFields(string $row): array
    {
        $fields = [];
        $buf = '';
        $inString = false;
        $len = strlen($row);
        for ($i = 0; $i < $len; $i++) {
            $c = $row[$i];
            if ($c === "'") {
                if ($inString && $i + 1 < $len && $row[$i + 1] === "'") {
                    $buf .= "''";
                    $i++;

                    continue;
                }
                $inString = !$inString;
                $buf .= $c;

                continue;
            }
            if (!$inString && $c === ',') {
                $fields[] = trim($buf);
                $buf = '';

                continue;
            }
            $buf .= $c;
        }
        if ($buf !== '') {
            $fields[] = trim($buf);
        }

        return $fields;
    }

    /**
     * @return string|null decoded string; NULL for SQL NULL
     */
    private function decodeSqlField(string $raw): ?string
    {
        $raw = trim($raw);
        if (strtoupper($raw) === 'NULL') {
            return null;
        }
        $len = strlen($raw);
        if ($len >= 2 && $raw[0] === "'" && substr($raw, -1) === "'") {
            $inner = substr($raw, 1, -1);

            return str_replace("''", "'", $inner);
        }

        return $raw;
    }

    private function normalizeAuthorName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name));
        $name = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $name);

        return Str::lower((string) $name);
    }
}
