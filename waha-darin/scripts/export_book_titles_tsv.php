<?php

/**
 * Export all books (id, title) as TSV to stdout.
 *
 * Usage:
 *   php scripts/export_book_titles_tsv.php > /tmp/book_titles.tsv
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/** @var \Illuminate\Database\Eloquent\Builder $q */
$q = \App\Models\Book::query()
    ->select(['id', 'title'])
    ->orderBy('id');

echo "book_id\tbook_title\n";

$q->chunkById(500, function ($books) {
    foreach ($books as $b) {
        $id = (int) $b->id;
        $title = (string) ($b->title ?? '');
        $title = str_replace(["\t", "\r", "\n"], [' ', ' ', ' '], $title);
        $title = trim(preg_replace('/\s+/u', ' ', $title) ?? $title);
        echo $id . "\t" . $title . "\n";
    }
});

