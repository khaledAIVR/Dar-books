<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublishStaticMediaCommand extends Command
{
    protected $signature = 'media:publish-from-storage
        {--dry-run : Show actions without writing files or updating the database}
        {--books : Only process book covers}
        {--authors : Only process author avatars}
        {--force : Re-copy even when the row already points under media/}';

    protected $description = 'Copy book/author images from the public storage disk into public/media/... and update books.image / authors.avatar to stable paths (media/covers/{id}.ext, media/authors/{id}.ext).';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $onlyBooks = (bool) $this->option('books');
        $onlyAuthors = (bool) $this->option('authors');
        $force = (bool) $this->option('force');

        $doBooks = !$onlyAuthors || $onlyBooks;
        $doAuthors = !$onlyBooks || $onlyAuthors;
        if (!$onlyBooks && !$onlyAuthors) {
            $doBooks = true;
            $doAuthors = true;
        }

        $disk = Storage::disk('public');
        $stats = ['books' => ['ok' => 0, 'skip' => 0, 'fail' => 0], 'authors' => ['ok' => 0, 'skip' => 0, 'fail' => 0]];

        if ($doBooks) {
            $this->info('Books: copying from storage to public/media/covers …');
            Book::query()->orderBy('id')->chunk(100, function ($books) use ($disk, $dry, $force, &$stats) {
                foreach ($books as $book) {
                    $r = $this->publishBookCover($book, $disk, $dry, $force);
                    $stats['books'][$r]++;
                }
            });
        }

        if ($doAuthors) {
            $this->info('Authors: copying from storage to public/media/authors …');
            Author::query()->orderBy('id')->chunk(100, function ($authors) use ($disk, $dry, $force, &$stats) {
                foreach ($authors as $author) {
                    $r = $this->publishAuthorAvatar($author, $disk, $dry, $force);
                    $stats['authors'][$r]++;
                }
            });
        }

        $this->table(
            ['', 'ok', 'skipped', 'failed'],
            [
                ['books', $stats['books']['ok'], $stats['books']['skip'], $stats['books']['fail']],
                ['authors', $stats['authors']['ok'], $stats['authors']['skip'], $stats['authors']['fail']],
            ]
        );

        if ($dry) {
            $this->warn('Dry run — no files or database changes were applied.');
        }

        return 0;
    }

    /**
     * @return string ok|skip|fail
     */
    private function publishBookCover(Book $book, $disk, bool $dry, bool $force): string
    {
        $raw = trim((string) (($book->getAttributes()['image'] ?? '')));
        if ($raw === '') {
            return 'skip';
        }

        if (Str::startsWith($raw, 'media/covers/') && !$force) {
            if (preg_match('#^media/covers/'.preg_quote((string) $book->id, '#').'\.[a-z0-9]+$#i', $raw)) {
                return 'skip';
            }
        }

        $bytes = null;
        $sourceRel = null;

        if (Str::startsWith($raw, 'media/')) {
            $abs = public_path($raw);
            if (!is_file($abs)) {
                $this->warn("Book {$book->id}: public file missing ({$raw})");

                return 'fail';
            }
            $bytes = file_get_contents($abs);
            $sourceRel = $raw;
        } else {
            $rel = $this->normalizeImagePathToDiskRelative($raw);
            if ($rel === null || !$disk->exists($rel)) {
                $this->warn("Book {$book->id}: source missing on public disk ({$raw})");

                return 'fail';
            }
            $bytes = $disk->get($rel);
            $sourceRel = $rel;
        }

        if ($bytes === false || $bytes === null) {
            $this->warn("Book {$book->id}: could not read {$sourceRel}");

            return 'fail';
        }

        $ext = strtolower(pathinfo($sourceRel, PATHINFO_EXTENSION) ?: 'jpg');
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg';
        $destRel = 'media/covers/'.$book->id.'.'.$ext;
        $destAbs = public_path($destRel);

        if (!$dry) {
            if (!is_dir(dirname($destAbs))) {
                mkdir(dirname($destAbs), 0755, true);
            }
            file_put_contents($destAbs, $bytes);
            $book->image = $destRel;
            $book->save();
        } else {
            $this->line("Book {$book->id}: would copy {$sourceRel} -> {$destRel}");
        }

        return 'ok';
    }

    /**
     * @return string ok|skip|fail
     */
    private function publishAuthorAvatar(Author $author, $disk, bool $dry, bool $force): string
    {
        $raw = trim((string) (($author->getAttributes()['avatar'] ?? '')));
        if ($raw === '') {
            return 'skip';
        }

        if (Str::startsWith($raw, 'media/authors/') && !$force) {
            if (preg_match('#^media/authors/'.preg_quote((string) $author->id, '#').'\.[a-z0-9]+$#i', $raw)) {
                return 'skip';
            }
        }

        $bytes = null;
        $sourceRel = null;

        if (Str::startsWith($raw, 'media/')) {
            $abs = public_path($raw);
            if (!is_file($abs)) {
                $this->warn("Author {$author->id}: public file missing ({$raw})");

                return 'fail';
            }
            $bytes = file_get_contents($abs);
            $sourceRel = $raw;
        } else {
            $rel = $this->normalizeImagePathToDiskRelative($raw);
            if ($rel === null || !$disk->exists($rel)) {
                $this->warn("Author {$author->id}: source missing on public disk ({$raw})");

                return 'fail';
            }
            $bytes = $disk->get($rel);
            $sourceRel = $rel;
        }

        if ($bytes === false || $bytes === null) {
            $this->warn("Author {$author->id}: could not read {$sourceRel}");

            return 'fail';
        }

        $ext = strtolower(pathinfo($sourceRel, PATHINFO_EXTENSION) ?: 'jpg');
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg';
        $destRel = 'media/authors/'.$author->id.'.'.$ext;
        $destAbs = public_path($destRel);

        if (!$dry) {
            if (!is_dir(dirname($destAbs))) {
                mkdir(dirname($destAbs), 0755, true);
            }
            file_put_contents($destAbs, $bytes);
            $author->avatar = $destRel;
            $author->save();
        } else {
            $this->line("Author {$author->id}: would copy {$sourceRel} -> {$destRel}");
        }

        return 'ok';
    }

    /**
     * Map DB value to a path relative to the public storage disk root.
     */
    private function normalizeImagePathToDiskRelative(string $image): ?string
    {
        $v = trim($image);
        if ($v === '') {
            return null;
        }

        if (Str::startsWith($v, ['http://', 'https://'])) {
            $path = (string) (parse_url($v, PHP_URL_PATH) ?? '');
            if ($path !== '' && Str::startsWith($path, '/storage/')) {
                $v = $path;
            } else {
                return null;
            }
        }

        $v = preg_replace('#^/+storage/+?#', '', $v) ?? $v;
        $v = preg_replace('#^storage/+?#', '', $v) ?? $v;
        $v = preg_replace('#^public/+?#', '', $v) ?? $v;
        $v = ltrim($v, '/');
        if ($v === '' || Str::contains($v, ['..', "\0"])) {
            return null;
        }

        return $v;
    }
}
