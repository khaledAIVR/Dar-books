<?php
/**
 * Verify there are no duplicates in an XLSX, using:
 *  1) Exact duplicates after normalization of (title + author + publisher)
 *  2) Fuzzy near-duplicates: high token overlap for title AND (author + publisher)
 *
 * Intended for the DB-export workbook layout:
 *   - Title:     column C
 *   - Author:    column D
 *   - Publisher: column E
 *
 * Usage (from waha-darin/):
 *   php scripts/check_duplicates_title_author_publisher_in_xlsx.php \
 *     --excel="storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED.xlsx" \
 *     --sheet="Sheet1" \
 *     --title-threshold=0.90 \
 *     --author-threshold=0.80 \
 *     --publisher-threshold=0.80 \
 *     --out-exact="storage/app/public/book-covers-by-title/_dupes_exact_title_author_publisher.tsv" \
 *     --out-fuzzy="storage/app/public/book-covers-by-title/_dupes_fuzzy_title_author_publisher.tsv"
 */
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelRel = 'storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED.xlsx';
$sheetName = 'Sheet1';
$titleThr = 0.90;
$authorThr = 0.80;
$publisherThr = 0.80;
$outExactRel = 'storage/app/public/book-covers-by-title/_dupes_exact_title_author_publisher.tsv';
$outFuzzyRel = 'storage/app/public/book-covers-by-title/_dupes_fuzzy_title_author_publisher.tsv';

foreach ($argv as $arg) {
    if (strpos($arg, '--excel=') === 0) {
        $excelRel = (string)substr($arg, strlen('--excel='));
    } elseif (strpos($arg, '--sheet=') === 0) {
        $sheetName = (string)substr($arg, strlen('--sheet='));
    } elseif (strpos($arg, '--title-threshold=') === 0) {
        $titleThr = (float)substr($arg, strlen('--title-threshold='));
    } elseif (strpos($arg, '--author-threshold=') === 0) {
        $authorThr = (float)substr($arg, strlen('--author-threshold='));
    } elseif (strpos($arg, '--publisher-threshold=') === 0) {
        $publisherThr = (float)substr($arg, strlen('--publisher-threshold='));
    } elseif (strpos($arg, '--out-exact=') === 0) {
        $outExactRel = (string)substr($arg, strlen('--out-exact='));
    } elseif (strpos($arg, '--out-fuzzy=') === 0) {
        $outFuzzyRel = (string)substr($arg, strlen('--out-fuzzy='));
    }
}

$basePath = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
$excelPath = $excelRel;
$outExactPath = $outExactRel;
$outFuzzyPath = $outFuzzyRel;
foreach (['excelPath' => &$excelPath, 'outExactPath' => &$outExactPath, 'outFuzzyPath' => &$outFuzzyPath] as &$ref) {
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
 * @return array<int, string>
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
        if (mb_substr($p, 0, 2, 'UTF-8') === 'ال' && mb_strlen($p, 'UTF-8') > 2) {
            $p = mb_substr($p, 2, null, 'UTF-8');
        }
        $out[] = $p;
    }
    return $out;
}

/**
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

// Extract rows (only rows with a title)
$rows = [];
for ($r = 1; $r <= $highestRow; $r++) {
    $title = normalizeSpaces((string)$sheet->getCell('C' . $r)->getValue());
    $author = normalizeSpaces((string)$sheet->getCell('D' . $r)->getValue());
    $publisher = normalizeSpaces((string)$sheet->getCell('E' . $r)->getValue());
    if ($title === '') {
        continue;
    }
    $rows[] = [
        'row' => $r,
        'title' => $title,
        'author' => $author,
        'publisher' => $publisher,
        'ntitle' => normalizeText($title),
        'nauthor' => normalizeText($author),
        'npublisher' => normalizeText($publisher),
        'ttoks' => tokens($title),
        'atoks' => tokens($author),
        'ptoks' => tokens($publisher),
    ];
}

$n = count($rows);
if ($n === 0) {
    echo "No usable rows found.\n";
    exit(0);
}

// 1) Exact normalized duplicates for triple key
$byKey = []; // key => list of row indices
for ($i = 0; $i < $n; $i++) {
    $k = $rows[$i]['ntitle'] . '|' . $rows[$i]['nauthor'] . '|' . $rows[$i]['npublisher'];
    $byKey[$k][] = $i;
}
$exactGroups = array_values(array_filter($byKey, function ($g) {
    return count($g) > 1;
}));

// Write exact report
$outDir = dirname($outExactPath);
if (!is_dir($outDir)) {
    @mkdir($outDir, 0777, true);
}
$fh = fopen($outExactPath, 'w');
if (!$fh) {
    fwrite(STDERR, "Unable to write: {$outExactPath}\n");
    exit(1);
}
fwrite($fh, "group_id\tgroup_size\trow\ttitle\tauthor\tpublisher\n");
$gid = 1;
foreach ($exactGroups as $g) {
    foreach ($g as $idx) {
        $r = $rows[$idx];
        $safeTitle = str_replace(["\t", "\r", "\n"], ['\\t', ' ', ' '], (string)$r['title']);
        $safeAuthor = str_replace(["\t", "\r", "\n"], ['\\t', ' ', ' '], (string)$r['author']);
        $safePub = str_replace(["\t", "\r", "\n"], ['\\t', ' ', ' '], (string)$r['publisher']);
        fwrite($fh, implode("\t", [
            (string)$gid,
            (string)count($g),
            (string)$r['row'],
            $safeTitle,
            $safeAuthor,
            $safePub,
        ]) . "\n");
    }
    $gid++;
}
fclose($fh);

// 2) Fuzzy duplicates with blocking
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

// Build multiple bucket keys per row to reduce false negatives
$buckets = []; // key => list of indices
for ($i = 0; $i < $n; $i++) {
    $toks = $rows[$i]['ttoks'];
    if (empty($toks)) {
        continue;
    }
    $t0 = $toks[0] ?? '';
    $tLast = $toks[count($toks) - 1] ?? '';
    $len = mb_strlen((string)$rows[$i]['ntitle'], 'UTF-8');
    $lb = (int)floor($len / 8);
    $buckets["f:{$t0}|{$lb}"][] = $i;
    if ($tLast !== '') {
        $buckets["l:{$tLast}|{$lb}"][] = $i;
    }
    if (count($toks) >= 2) {
        $buckets["2:{$toks[0]}+{$toks[1]}|{$lb}"][] = $i;
    }
}

$pairsCompared = 0;
$pairsMatched = 0;
$seenPairs = []; // "a-b" => true

foreach ($buckets as $idxs) {
    $m = count($idxs);
    if ($m < 2) {
        continue;
    }
    for ($ii = 0; $ii < $m; $ii++) {
        $a = $idxs[$ii];
        for ($jj = $ii + 1; $jj < $m; $jj++) {
            $b = $idxs[$jj];
            $x = $a < $b ? "{$a}-{$b}" : "{$b}-{$a}";
            if (isset($seenPairs[$x])) {
                continue;
            }
            $seenPairs[$x] = true;

            $pairsCompared++;

            $tsim = jaccard($rows[$a]['ttoks'], $rows[$b]['ttoks']);
            if ($tsim < $titleThr) {
                continue;
            }

            // author: if either missing, require stricter title similarity
            $asim = jaccard($rows[$a]['atoks'], $rows[$b]['atoks']);
            if (empty($rows[$a]['atoks']) || empty($rows[$b]['atoks'])) {
                if ($tsim < min(0.97, $titleThr + 0.05)) {
                    continue;
                }
            } else {
                if ($asim < $authorThr) {
                    continue;
                }
            }

            // publisher: if either missing, require stricter title similarity
            $psim = jaccard($rows[$a]['ptoks'], $rows[$b]['ptoks']);
            if (empty($rows[$a]['ptoks']) || empty($rows[$b]['ptoks'])) {
                if ($tsim < min(0.97, $titleThr + 0.05)) {
                    continue;
                }
            } else {
                if ($psim < $publisherThr) {
                    continue;
                }
            }

            $union($a, $b);
            $pairsMatched++;
        }
    }
}

// Build fuzzy groups
$groups = [];
for ($i = 0; $i < $n; $i++) {
    $root = $find($i);
    $groups[$root][] = $i;
}
$fuzzyGroups = array_values(array_filter($groups, function ($g) {
    return count($g) > 1;
}));
usort($fuzzyGroups, function ($a, $b) {
    return count($b) <=> count($a);
});

// Write fuzzy report
$outDir2 = dirname($outFuzzyPath);
if (!is_dir($outDir2)) {
    @mkdir($outDir2, 0777, true);
}
$fh2 = fopen($outFuzzyPath, 'w');
if (!$fh2) {
    fwrite(STDERR, "Unable to write: {$outFuzzyPath}\n");
    exit(1);
}
fwrite($fh2, "group_id\tgroup_size\ttitle_thr\tauthor_thr\tpublisher_thr\trow\ttitle\tauthor\tpublisher\n");
$gid2 = 1;
foreach ($fuzzyGroups as $g) {
    foreach ($g as $idx) {
        $r = $rows[$idx];
        $safeTitle = str_replace(["\t", "\r", "\n"], ['\\t', ' ', ' '], (string)$r['title']);
        $safeAuthor = str_replace(["\t", "\r", "\n"], ['\\t', ' ', ' '], (string)$r['author']);
        $safePub = str_replace(["\t", "\r", "\n"], ['\\t', ' ', ' '], (string)$r['publisher']);
        fwrite($fh2, implode("\t", [
            (string)$gid2,
            (string)count($g),
            (string)$titleThr,
            (string)$authorThr,
            (string)$publisherThr,
            (string)$r['row'],
            $safeTitle,
            $safeAuthor,
            $safePub,
        ]) . "\n");
    }
    $gid2++;
}
fclose($fh2);

echo "Workbook: {$excelPath}\n";
echo "Sheet: {$sheetName}\n";
echo "Rows analyzed (title present): {$n}\n";
echo "Exact normalized duplicate groups: " . count($exactGroups) . "\n";
echo "Exact normalized duplicate rows (extra): " . array_sum(array_map(function ($g) { return count($g) - 1; }, $exactGroups)) . "\n";
echo "Fuzzy duplicate groups: " . count($fuzzyGroups) . "\n";
echo "Fuzzy duplicate rows (extra): " . array_sum(array_map(function ($g) { return count($g) - 1; }, $fuzzyGroups)) . "\n";
echo "Compared pairs (blocked, unique): {$pairsCompared}\n";
echo "Matched pairs (unioned): {$pairsMatched}\n";
echo "Exact report: {$outExactPath}\n";
echo "Fuzzy report: {$outFuzzyPath}\n";

