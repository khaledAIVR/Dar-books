<?php
/**
 * Inspect an Excel workbook (XLSX/XLS) and print:
 * - sheet names
 * - active sheet title
 * - first non-empty rows (preview) for each sheet
 *
 * Usage:
 *   php scripts/inspect_xlsx.php "path/to/file.xlsx"
 */
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? '';
if ($path === '') {
    fwrite(STDERR, "Missing path argument.\n");
    exit(2);
}
if (!is_file($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(2);
}

$ss = IOFactory::load($path);
$names = $ss->getSheetNames();
echo "sheets=" . json_encode($names, JSON_UNESCAPED_UNICODE) . "\n";

$activeTitle = $ss->getActiveSheet()->getTitle();
echo "active=" . $activeTitle . "\n";

foreach ($names as $sheetName) {
    $sheet = $ss->getSheetByName($sheetName);
    if (!$sheet) {
        continue;
    }
    echo "--- sheet={$sheetName} ---\n";

    $matrix = $sheet->toArray(null, true, true, true);
    $shown = 0;
    foreach ($matrix as $r => $cols) {
        $hasAny = false;
        foreach ($cols as $v) {
            if (trim((string)$v) !== '') {
                $hasAny = true;
                break;
            }
        }
        if (!$hasAny) {
            continue;
        }

        $out = [];
        $i = 0;
        foreach ($cols as $colLetter => $v) {
            $out[$colLetter] = $v;
            $i++;
            if ($i >= 20) {
                break;
            }
        }

        echo "row={$r} first20=" . json_encode($out, JSON_UNESCAPED_UNICODE) . "\n";
        $shown++;
        if ($shown >= 12) {
            break;
        }
    }
}

