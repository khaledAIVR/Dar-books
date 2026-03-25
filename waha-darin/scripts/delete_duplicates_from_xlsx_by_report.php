<?php
/**
 * Delete duplicates from an XLSX based on a fuzzy-duplicate TSV report.
 *
 * We keep ONE row per group (the smallest row number), and delete the rest.
 * Deletions are applied in descending row order to avoid index shifting.
 *
 * Usage (from waha-darin/):
 *   php scripts/delete_duplicates_from_xlsx_by_report.php \
 *     --excel="storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910.xlsx" \
 *     --sheet="Sheet1" \
 *     --report="storage/app/public/book-covers-by-title/_fuzzy_duplicates_thr092.tsv" \
 *     --out="storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED.xlsx"
 */
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelRel = 'storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910.xlsx';
$sheetName = 'Sheet1';
$reportRel = 'storage/app/public/book-covers-by-title/_fuzzy_duplicates_thr092.tsv';
$outRel = 'storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED.xlsx';

foreach ($argv as $arg) {
    if (strpos($arg, '--excel=') === 0) {
        $excelRel = (string)substr($arg, strlen('--excel='));
    } elseif (strpos($arg, '--sheet=') === 0) {
        $sheetName = (string)substr($arg, strlen('--sheet='));
    } elseif (strpos($arg, '--report=') === 0) {
        $reportRel = (string)substr($arg, strlen('--report='));
    } elseif (strpos($arg, '--out=') === 0) {
        $outRel = (string)substr($arg, strlen('--out='));
    }
}

$basePath = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
$excelPath = $excelRel;
$reportPath = $reportRel;
$outPath = $outRel;
foreach (['excelPath' => &$excelPath, 'reportPath' => &$reportPath, 'outPath' => &$outPath] as &$ref) {
    if (!preg_match('#^/#', $ref)) {
        $ref = $basePath . '/' . ltrim($ref, '/');
    }
}

if (!is_file($excelPath)) {
    fwrite(STDERR, "Excel not found: {$excelPath}\n");
    exit(1);
}
if (!is_file($reportPath)) {
    fwrite(STDERR, "Report TSV not found: {$reportPath}\n");
    exit(1);
}

// Read report TSV
$fh = fopen($reportPath, 'r');
if (!$fh) {
    fwrite(STDERR, "Unable to open report TSV: {$reportPath}\n");
    exit(1);
}

$header = fgetcsv($fh, 0, "\t");
if (!$header) {
    fwrite(STDERR, "Report TSV is empty: {$reportPath}\n");
    exit(1);
}

$idxGroup = array_search('group_id', $header, true);
$idxRow = array_search('row', $header, true);
$idxTitle = array_search('title', $header, true);
$idxAuthor = array_search('author', $header, true);
if ($idxGroup === false || $idxRow === false) {
    fwrite(STDERR, "Report TSV missing required columns (group_id, row).\n");
    exit(1);
}

$groups = []; // group_id => list of ['row'=>int,'title'=>string,'author'=>string]
while (($row = fgetcsv($fh, 0, "\t")) !== false) {
    $gid = trim((string)($row[$idxGroup] ?? ''));
    $rno = trim((string)($row[$idxRow] ?? ''));
    if ($gid === '' || $rno === '' || !ctype_digit($rno)) {
        continue;
    }
    $groups[$gid][] = [
        'row' => (int)$rno,
        'title' => (string)($idxTitle !== false ? ($row[$idxTitle] ?? '') : ''),
        'author' => (string)($idxAuthor !== false ? ($row[$idxAuthor] ?? '') : ''),
    ];
}
fclose($fh);

if (empty($groups)) {
    echo "No groups found in report. Nothing to delete.\n";
    exit(0);
}

// Decide which rows to delete
$toDelete = []; // rowNumber => ['group_id'=>..., 'title'=>..., 'author'=>...]
$kept = [];     // group_id => keptRow

foreach ($groups as $gid => $items) {
    $rows = array_map(function ($x) {
        return (int)($x['row'] ?? 0);
    }, $items);
    $rows = array_values(array_filter($rows, function ($n) {
        return $n > 0;
    }));
    if (count($rows) < 2) {
        continue;
    }
    sort($rows);
    $keepRow = $rows[0];
    $kept[$gid] = $keepRow;

    // delete all other rows
    foreach ($items as $it) {
        $rno = (int)($it['row'] ?? 0);
        if ($rno <= 0 || $rno === $keepRow) {
            continue;
        }
        // avoid duplicates in delete list
        if (!isset($toDelete[$rno])) {
            $toDelete[$rno] = [
                'group_id' => (string)$gid,
                'kept_row' => (int)$keepRow,
                'title' => (string)($it['title'] ?? ''),
                'author' => (string)($it['author'] ?? ''),
            ];
        }
    }
}

if (empty($toDelete)) {
    echo "No rows to delete (all groups are size 1?).\n";
    exit(0);
}

// Load workbook and delete rows in descending order
$ss = IOFactory::load($excelPath);
$sheet = $ss->getSheetByName($sheetName);
if (!$sheet) {
    fwrite(STDERR, "Sheet not found: {$sheetName}\n");
    exit(1);
}

$highestRow = (int)$sheet->getHighestDataRow();
$deleteRows = array_keys($toDelete);
rsort($deleteRows, SORT_NUMERIC);

$deleted = 0;
$skippedOutOfRange = 0;
foreach ($deleteRows as $rno) {
    if ($rno < 1 || $rno > $highestRow) {
        $skippedOutOfRange++;
        continue;
    }
    $sheet->removeRow($rno, 1);
    $deleted++;
    // after removing one row, highestRow decreases by 1
    $highestRow--;
}

// Save output file
$outDir = dirname($outPath);
if (!is_dir($outDir)) {
    @mkdir($outDir, 0777, true);
}

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
$writer->save($outPath);

// Write deletion report TSV next to output
$deletedReportPath = preg_replace('/\\.xlsx$/i', '', $outPath) . '_deleted_rows.tsv';
if (is_string($deletedReportPath)) {
    $rfh = @fopen($deletedReportPath, 'w');
    if ($rfh) {
        fwrite($rfh, "deleted_row\tgroup_id\tkept_row\ttitle\tauthor\n");
        foreach ($deleteRows as $rno) {
            if (!isset($toDelete[$rno])) {
                continue;
            }
            $it = $toDelete[$rno];
            $safeTitle = str_replace(["\t", "\r", "\n"], ['\\t', ' ', ' '], (string)($it['title'] ?? ''));
            $safeAuthor = str_replace(["\t", "\r", "\n"], ['\\t', ' ', ' '], (string)($it['author'] ?? ''));
            fwrite($rfh, implode("\t", [
                (string)$rno,
                (string)($it['group_id'] ?? ''),
                (string)($it['kept_row'] ?? ''),
                $safeTitle,
                $safeAuthor,
            ]) . "\n");
        }
        fclose($rfh);
    }
}

echo "Input workbook: {$excelPath}\n";
echo "Sheet: {$sheetName}\n";
echo "Groups in report: " . count($groups) . "\n";
echo "Rows requested for deletion: " . count($toDelete) . "\n";
echo "Rows deleted: {$deleted}\n";
echo "Skipped (out of range): {$skippedOutOfRange}\n";
echo "Wrote: {$outPath}\n";
if (is_string($deletedReportPath)) {
    echo "Deletion report: {$deletedReportPath}\n";
}

