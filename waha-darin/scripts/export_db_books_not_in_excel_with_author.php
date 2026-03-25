<?php

/**
 * Export DB books NOT represented in the Excel-matched report, including author.
 *
 * It uses the matched report TSV (Excel -> best_db_title) to build a set of DB titles
 * that are considered "in Excel", then outputs all DB books whose normalized title
 * is NOT in that set.
 *
 * Usage (from waha-darin/):
 *   php scripts/export_db_books_not_in_excel_with_author.php \
 *     --matched="storage/app/public/book-covers-by-title/_excel_colA_db_fuzzy_matched_ge_97_plus_llm.tsv" \
 *     --out="storage/app/public/book-covers-by-title/_db_books_not_in_excel_with_author.tsv"
 */

require __DIR__ . '/../vendor/autoload.php';

$matchedRel = 'storage/app/public/book-covers-by-title/_excel_colA_db_fuzzy_matched_ge_97_plus_llm.tsv';
$outRel = 'storage/app/public/book-covers-by-title/_db_books_not_in_excel_with_author.tsv';

foreach ($argv as $arg) {
    if (strpos($arg, '--matched=') === 0) {
        $matchedRel = (string)substr($arg, strlen('--matched='));
    } elseif (strpos($arg, '--out=') === 0) {
        $outRel = (string)substr($arg, strlen('--out='));
    }
}

$basePath = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
$matchedPath = $matchedRel;
$outPath = $outRel;

if (!preg_match('#^/#', $matchedPath)) {
    $matchedPath = $basePath . '/' . ltrim($matchedPath, '/');
}
if (!preg_match('#^/#', $outPath)) {
    $outPath = $basePath . '/' . ltrim($outPath, '/');
}

if (!file_exists($matchedPath)) {
    fwrite(STDERR, "Matched TSV not found: {$matchedPath}\n");
    exit(1);
}

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Book;

/**
 * Aggressive Arabic-friendly normalization (must stay consistent with earlier matching).
 */
function normalizeTitle(string $s): string
{
    $s = str_replace("\u{00A0}", ' ', $s); // NBSP
    $s = trim($s);
    if ($s === '') {
        return '';
    }

    $map = [
        'أ' => 'ا',
        'إ' => 'ا',
        'آ' => 'ا',
        'ٱ' => 'ا',
        'ؤ' => 'و',
        'ئ' => 'ي',
        'ى' => 'ي',
        'ة' => 'ه',
        'ـ' => '',
    ];
    $s = strtr($s, $map);

    // remove Arabic diacritics
    $s = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $s) ?? $s;

    // replace punctuation/symbols with spaces (keep letters/numbers/spaces)
    $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s) ?? $s;

    // collapse whitespace
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    $s = trim($s);

    if (function_exists('mb_strtolower')) {
        $s = mb_strtolower($s, 'UTF-8');
    } else {
        $s = strtolower($s);
    }

    return $s;
}

// 1) Build a set of DB titles that are represented in Excel (from matched TSV best_db_title).
$fh = fopen($matchedPath, 'r');
if (!$fh) {
    fwrite(STDERR, "Unable to open matched TSV: {$matchedPath}\n");
    exit(1);
}

$header = fgetcsv($fh, 0, "\t");
if (!$header || !is_array($header)) {
    fwrite(STDERR, "Matched TSV header unreadable: {$matchedPath}\n");
    exit(1);
}

$idx = array_search('best_db_title', $header, true);
if ($idx === false) {
    fwrite(STDERR, "Matched TSV missing best_db_title column.\n");
    exit(1);
}

$inExcel = []; // normalized => true
$matchedRows = 0;
while (($row = fgetcsv($fh, 0, "\t")) !== false) {
    $matchedRows++;
    $t = (string)($row[$idx] ?? '');
    $t = trim($t);
    if ($t === '') {
        continue;
    }
    $n = normalizeTitle($t);
    if ($n !== '') {
        $inExcel[$n] = true;
    }
}
fclose($fh);

// 2) Export DB books not in that set, with author.
$outDir = dirname($outPath);
if (!is_dir($outDir)) {
    @mkdir($outDir, 0777, true);
}

$out = fopen($outPath, 'w');
if (!$out) {
    fwrite(STDERR, "Unable to open output for writing: {$outPath}\n");
    exit(1);
}

fwrite($out, implode("\t", [
    'book_id',
    'book_title',
    'author_id',
    'author_name',
]) . "\n");

$exported = 0;
$skippedEmptyTitle = 0;

Book::query()
    ->select(['id', 'title', 'author_id'])
    ->with(['author:id,name'])
    ->orderBy('id')
    ->chunkById(300, function ($books) use (&$exported, &$skippedEmptyTitle, $inExcel, $out) {
        foreach ($books as $b) {
            $title = (string)($b->title ?? '');
            $title = trim($title);
            if ($title === '') {
                $skippedEmptyTitle++;
                continue;
            }

            $n = normalizeTitle($title);
            if ($n !== '' && isset($inExcel[$n])) {
                continue;
            }

            $authorId = $b->author_id ? (string)$b->author_id : '';
            $authorName = $b->author ? (string)($b->author->name ?? '') : '';

            $safeTitle = str_replace(["\t", "\r", "\n"], ['\\t', '\\r', '\\n'], $title);
            $safeAuthor = str_replace(["\t", "\r", "\n"], ['\\t', '\\r', '\\n'], $authorName);

            fwrite($out, implode("\t", [
                (string)$b->id,
                $safeTitle,
                $authorId,
                $safeAuthor,
            ]) . "\n");
            $exported++;
        }
    });

fclose($out);

echo "Matched TSV rows scanned: {$matchedRows}\n";
echo "Unique normalized titles considered 'in Excel': " . count($inExcel) . "\n";
echo "Exported DB books not in Excel: {$exported}\n";
echo "Skipped (empty title): {$skippedEmptyTitle}\n";
echo "Wrote: {$outPath}\n";

