<?php
/**
 * Count near-duplicates (not exact) in an XLSX using Arabic-friendly normalization + token overlap.
 *
 * Default behavior is conservative: duplicates require high title similarity AND (by default) same-ish author.
 *
 * Workbook layout expected (your DB-export style):
 * - Title in column C
 * - Author in column D
 *
 * Usage (from waha-darin/):
 *   php scripts/count_fuzzy_duplicates_in_xlsx.php \
 *     --excel="storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910.xlsx" \
 *     --sheet="Sheet1" \
 *     --threshold=0.92 \
 *     --require-author=1 \
 *     --out="storage/app/public/book-covers-by-title/_fuzzy_duplicates.tsv"
 */
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelRel = 'storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910.xlsx';
$sheetName = 'Sheet1';
$threshold = 0.92; // Jaccard/token similarity (0..1)
$requireAuthor = true;
$outRel = 'storage/app/public/book-covers-by-title/_fuzzy_duplicates.tsv';

foreach ($argv as $arg) {
    if (strpos($arg, '--excel=') === 0) {
        $excelRel = (string)substr($arg, strlen('--excel='));
    } elseif (strpos($arg, '--sheet=') === 0) {
        $sheetName = (string)substr($arg, strlen('--sheet='));
    } elseif (strpos($arg, '--threshold=') === 0) {
        $threshold = (float)substr($arg, strlen('--threshold='));
    } elseif (strpos($arg, '--require-author=') === 0) {
        $v = trim((string)substr($arg, strlen('--require-author=')));
        $requireAuthor = !in_array(strtolower($v), ['0', 'false', 'no', 'n'], true);
    } elseif (strpos($arg, '--out=') === 0) {
        $outRel = (string)substr($arg, strlen('--out='));
    }
}

$basePath = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
$excelPath = $excelRel;
$outPath = $outRel;
foreach (['excelPath' => &$excelPath, 'outPath' => &$outPath] as &$ref) {
    if (!preg_match('#^/#', $ref)) {
        $ref = $basePath . '/' . ltrim($ref, '/');
    }
}
if (!is_file($excelPath)) {
    fwrite(STDERR, "Excel not found: {$excelPath}\n");
    exit(1);
}

function normalizeSpaces(string $s): string
{
    $s = str_replace("\u{00A0}", ' ', $s);
    $s = preg_replace('/\s+/u', ' ', trim($s));
    return $s ?? '';
}

function normalizeText(string $s): string
{
    $s = normalizeSpaces($s);
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
        '،' => ' ',
        '؛' => ' ',
    ];
    $s = strtr($s, $map);
    $s = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $s) ?? $s;
    $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s) ?? $s;
    $s = normalizeSpaces($s);
    $s = function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    return $s;
}

/**
 * @return array<int, string> tokens
 */
function tokens(string $s): array
{
    $s = normalizeText($s);
    if ($s === '') {
        return [];
    }
    $parts = preg_split('/\s+/u', $s) ?: [];
    $out = [];
    foreach ($parts as $p) {
        $p = trim((string)$p);
        if ($p === '') {
            continue;
        }
        // Light Arabic prefix stripping for comparison
        if (mb_substr($p, 0, 2, 'UTF-8') === 'ال' && mb_strlen($p, 'UTF-8') > 2) {
            $p = mb_substr($p, 2, null, 'UTF-8');
        }
        $out[] = $p;
    }
    return $out;
}

/**
 * Token Jaccard similarity in [0..1]
 *
 * @param array<int, string> $a
 * @param array<int, string> $b
 */
function jaccard(array $a, array $b): float
{
    if (empty($a) || empty($b)) {
        return 0.0;
    }
    $sa = array_fill_keys($a, true);
    $sb = array_fill_keys($b, true);
    $inter = 0;
    foreach ($sa as $k => $_) {
        if (isset($sb[$k])) {
            $inter++;
        }
    }
    $union = count($sa) + count($sb) - $inter;
    return $union > 0 ? ($inter / $union) : 0.0;
}

// Load workbook
$ss = IOFactory::load($excelPath);
$sheet = $ss->getSheetByName($sheetName);
if (!$sheet) {
    fwrite(STDERR, "Sheet not found: {$sheetName}\n");
    exit(1);
}

$highestRow = (int)$sheet->getHighestDataRow();

// Extract rows
$rows = []; // idx => data
for ($r = 1; $r <= $highestRow; $r++) {
    $title = normalizeSpaces((string)$sheet->getCell('C' . $r)->getValue());
    $author = normalizeSpaces((string)$sheet->getCell('D' . $r)->getValue());
    if ($title === '' && $author === '') {
        continue;
    }
    $toks = tokens($title);
    if (empty($toks)) {
        continue;
    }
    $atoks = tokens($author);
    $rows[] = [
        'row' => $r,
        'title' => $title,
        'author' => $author,
        'toks' => $toks,
        'atoks' => $atoks,
        'len' => mb_strlen(normalizeText($title), 'UTF-8'),
    ];
}

$n = count($rows);
if ($n === 0) {
    echo "No usable rows found.\n";
    exit(0);
}

// Union-Find
$parent = range(0, $n - 1);
$rank = array_fill(0, $n, 0);
$find = function ($x) use (&$parent, &$find) {
    if ($parent[$x] !== $x) {
        $parent[$x] = $find($parent[$x]);
    }
    return $parent[$x];
};
$union = function ($a, $b) use (&$parent, &$rank, $find) {
    $ra = $find($a);
    $rb = $find($b);
    if ($ra === $rb) {
        return;
    }
    if ($rank[$ra] < $rank[$rb]) {
        $parent[$ra] = $rb;
    } elseif ($rank[$ra] > $rank[$rb]) {
        $parent[$rb] = $ra;
    } else {
        $parent[$rb] = $ra;
        $rank[$ra]++;
    }
};

// Blocking to avoid O(n^2) worst-case: group by first token + length bucket
$buckets = []; // key => list of indices
for ($i = 0; $i < $n; $i++) {
    $t0 = $rows[$i]['toks'][0] ?? '';
    $lb = (int)floor(((int)$rows[$i]['len']) / 8); // bucket by 8 chars
    $key = $t0 . '|' . $lb;
    $buckets[$key][] = $i;
}

$pairsCompared = 0;
$pairsMatched = 0;

foreach ($buckets as $key => $idxs) {
    $m = count($idxs);
    if ($m < 2) {
        continue;
    }
    // compare within bucket
    for ($ii = 0; $ii < $m; $ii++) {
        $a = $idxs[$ii];
        for ($jj = $ii + 1; $jj < $m; $jj++) {
            $b = $idxs[$jj];
            $pairsCompared++;

            $sim = jaccard($rows[$a]['toks'], $rows[$b]['toks']);
            if ($sim < $threshold) {
                continue;
            }

            if ($requireAuthor) {
                $asim = jaccard($rows[$a]['atoks'], $rows[$b]['atoks']);
                // If either author is missing, be stricter on title
                if (empty($rows[$a]['atoks']) || empty($rows[$b]['atoks'])) {
                    if ($sim < min(0.96, $threshold + 0.04)) {
                        continue;
                    }
                } else {
                    if ($asim < 0.80) {
                        continue;
                    }
                }
            }

            $union($a, $b);
            $pairsMatched++;
        }
    }
}

// Build groups
$groups = [];
for ($i = 0; $i < $n; $i++) {
    $root = $find($i);
    $groups[$root][] = $i;
}

$clusterCount = count($groups);
$duplicateCount = $n - $clusterCount;
$multiGroups = array_values(array_filter($groups, function ($g) {
    return count($g) > 1;
}));
usort($multiGroups, function ($a, $b) {
    return count($b) <=> count($a);
});

// Write TSV report (only groups with size>1)
$outDir = dirname($outPath);
if (!is_dir($outDir)) {
    @mkdir($outDir, 0777, true);
}
$fh = fopen($outPath, 'w');
if (!$fh) {
    fwrite(STDERR, "Unable to write: {$outPath}\n");
    exit(1);
}
fwrite($fh, "group_id\tgroup_size\tsim_threshold\trequire_author\trow\ttitle\tauthor\n");
$gid = 1;
foreach ($multiGroups as $g) {
    foreach ($g as $idx) {
        $r = $rows[$idx];
        $safeTitle = str_replace(["\t", "\r", "\n"], ['\\t', ' ', ' '], (string)$r['title']);
        $safeAuthor = str_replace(["\t", "\r", "\n"], ['\\t', ' ', ' '], (string)$r['author']);
        fwrite($fh, implode("\t", [
            (string)$gid,
            (string)count($g),
            (string)$threshold,
            $requireAuthor ? '1' : '0',
            (string)$r['row'],
            $safeTitle,
            $safeAuthor,
        ]) . "\n");
    }
    $gid++;
}
fclose($fh);

echo "Workbook: {$excelPath}\n";
echo "Sheet: {$sheetName}\n";
echo "Rows analyzed: {$n}\n";
echo "Clusters (unique after fuzzy grouping): {$clusterCount}\n";
echo "Near-duplicate rows (counted as duplicates): {$duplicateCount}\n";
echo "Duplicate groups (size>1): " . count($multiGroups) . "\n";
echo "Compared pairs (blocked): {$pairsCompared}\n";
echo "Matched pairs (unioned): {$pairsMatched}\n";
echo "Report: {$outPath}\n";

