<?php
/**
 * Add cover info columns to the DB-export style XLSX by matching titles to exported cover mapping.
 *
 * Inputs:
 * - Excel workbook (DB-export style): Title in column C
 * - Cover mapping TSV: storage/app/public/book-covers-by-title/_mapping.tsv
 *
 * Output:
 * - New workbook with 2 extra columns:
 *   - has_image (0/1)
 *   - cover_name (filename, e.g. "foo.webp")
 * - Unmatched and ambiguous match reports (TSV)
 *
 * Usage (from waha-darin/):
 *   php scripts/add_cover_columns_to_xlsx.php \
 *     --excel="storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED.xlsx" \
 *     --sheet="Sheet1" \
 *     --mapping="storage/app/public/book-covers-by-title/_mapping.tsv" \
 *     --covers-root="storage/app/public" \
 *     --out="storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED_WITH_COVERS.xlsx"
 */
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$excelRel = 'storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED.xlsx';
$sheetName = 'Sheet1';
$mappingRel = 'storage/app/public/book-covers-by-title/_mapping.tsv';
$coversRootRel = 'storage/app/public';
$outRel = 'storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED_WITH_COVERS.xlsx';

foreach ($argv as $arg) {
    if (strpos($arg, '--excel=') === 0) {
        $excelRel = (string)substr($arg, strlen('--excel='));
    } elseif (strpos($arg, '--sheet=') === 0) {
        $sheetName = (string)substr($arg, strlen('--sheet='));
    } elseif (strpos($arg, '--mapping=') === 0) {
        $mappingRel = (string)substr($arg, strlen('--mapping='));
    } elseif (strpos($arg, '--covers-root=') === 0) {
        $coversRootRel = (string)substr($arg, strlen('--covers-root='));
    } elseif (strpos($arg, '--out=') === 0) {
        $outRel = (string)substr($arg, strlen('--out='));
    }
}

$basePath = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
$excelPath = $excelRel;
$mappingPath = $mappingRel;
$coversRoot = $coversRootRel;
$outPath = $outRel;

foreach (['excelPath' => &$excelPath, 'mappingPath' => &$mappingPath, 'coversRoot' => &$coversRoot, 'outPath' => &$outPath] as &$ref) {
    if (!preg_match('#^/#', $ref)) {
        $ref = $basePath . '/' . ltrim($ref, '/');
    }
}

if (!is_file($excelPath)) {
    fwrite(STDERR, "Excel not found: {$excelPath}\n");
    exit(1);
}
if (!is_file($mappingPath)) {
    fwrite(STDERR, "Mapping TSV not found: {$mappingPath}\n");
    exit(1);
}
if (!is_dir($coversRoot)) {
    fwrite(STDERR, "Covers root dir not found: {$coversRoot}\n");
    exit(1);
}

function normalizeSpaces(string $s): string
{
    $s = str_replace("\u{00A0}", ' ', $s);
    $s = preg_replace('/\s+/u', ' ', trim($s));
    return $s ?? '';
}

/**
 * Aggressive Arabic-friendly normalization for title matching.
 */
function normalizeTitle(string $s): string
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

    // remove diacritics
    $s = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $s) ?? $s;

    // replace punctuation with spaces (keep letters/numbers/spaces)
    $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s) ?? $s;

    // collapse whitespace + lowercase
    $s = normalizeSpaces($s);
    $s = function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    return $s;
}

/**
 * Read mapping TSV into normalized_title => list of entries
 *
 * @return array<string, array<int, array{new_rel_path:string, book_id:int, book_title:string, src_rel_path:string}>>
 */
function readMapping(string $mappingPath): array
{
    $fh = fopen($mappingPath, 'r');
    if (!$fh) {
        throw new RuntimeException("Unable to open mapping TSV: {$mappingPath}");
    }

    $header = fgetcsv($fh, 0, "\t");
    if (!$header) {
        fclose($fh);
        throw new RuntimeException("Mapping TSV missing header: {$mappingPath}");
    }

    $idxNew = array_search('new_rel_path', $header, true);
    $idxId = array_search('book_id', $header, true);
    $idxTitle = array_search('book_title', $header, true);
    $idxSrc = array_search('src_rel_path', $header, true);
    if ($idxNew === false || $idxId === false || $idxTitle === false || $idxSrc === false) {
        fclose($fh);
        throw new RuntimeException("Mapping TSV missing required columns.");
    }

    $map = [];
    while (($row = fgetcsv($fh, 0, "\t")) !== false) {
        $newRel = normalizeSpaces((string)($row[$idxNew] ?? ''));
        $idRaw = normalizeSpaces((string)($row[$idxId] ?? ''));
        $title = normalizeSpaces((string)($row[$idxTitle] ?? ''));
        $srcRel = normalizeSpaces((string)($row[$idxSrc] ?? ''));
        if ($newRel === '' || $idRaw === '' || $title === '') {
            continue;
        }
        $bookId = ctype_digit($idRaw) ? (int)$idRaw : 0;
        $key = normalizeTitle($title);
        if ($key === '') {
            continue;
        }
        $map[$key][] = [
            'new_rel_path' => $newRel,
            'book_id' => $bookId,
            'book_title' => $title,
            'src_rel_path' => $srcRel,
        ];
    }
    fclose($fh);
    return $map;
}

$mapping = readMapping($mappingPath);

// Load workbook
$ss = IOFactory::load($excelPath);
$sheet = $ss->getSheetByName($sheetName);
if (!$sheet) {
    fwrite(STDERR, "Sheet not found: {$sheetName}\n");
    exit(1);
}

$highestRow = (int)$sheet->getHighestDataRow();
$highestCol = $sheet->getHighestDataColumn();
$highestColIndex = Coordinate::columnIndexFromString($highestCol);

// Add 2 new columns at the end
$hasImageColIndex = $highestColIndex + 1;
$coverNameColIndex = $highestColIndex + 2;
$hasImageCol = Coordinate::stringFromColumnIndex($hasImageColIndex);
$coverNameCol = Coordinate::stringFromColumnIndex($coverNameColIndex);

$matched = 0;
$unmatched = 0;
$ambiguous = 0;
$missingFile = 0;

$unmatchedRows = [];
$ambiguousRows = [];

for ($r = 1; $r <= $highestRow; $r++) {
    $title = normalizeSpaces((string)$sheet->getCell('C' . $r)->getValue());
    if ($title === '') {
        // still write blanks so columns are consistent
        $sheet->setCellValueExplicit($hasImageCol . $r, '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit($coverNameCol . $r, '', DataType::TYPE_STRING);
        continue;
    }

    $key = normalizeTitle($title);
    $entries = $mapping[$key] ?? [];

    $hasImage = 0;
    $coverName = '';

    if (count($entries) === 0) {
        $unmatched++;
        $unmatchedRows[] = ['row' => $r, 'title' => $title];
    } else {
        // Choose best entry:
        // 1) exact title match
        // 2) otherwise first
        $chosen = $entries[0];
        if (count($entries) > 1) {
            $foundExact = false;
            foreach ($entries as $e) {
                if (normalizeSpaces($e['book_title']) === $title) {
                    $chosen = $e;
                    $foundExact = true;
                    break;
                }
            }
            if (!$foundExact) {
                $ambiguous++;
                $ambiguousRows[] = [
                    'row' => $r,
                    'title' => $title,
                    'candidates' => implode(' | ', array_map(function ($e) {
                        return (string)($e['book_id'] ?? 0) . ':' . (string)($e['new_rel_path'] ?? '');
                    }, $entries)),
                ];
            }
        }

        $rel = (string)($chosen['new_rel_path'] ?? '');
        $coverName = basename($rel);

        $fullPath = rtrim($coversRoot, '/') . '/' . ltrim($rel, '/');
        if ($rel !== '' && is_file($fullPath)) {
            $hasImage = 1;
            $matched++;
        } else {
            $missingFile++;
            $hasImage = 0;
            $coverName = $coverName ?: '';
        }
    }

    $sheet->setCellValueExplicit($hasImageCol . $r, (string)$hasImage, DataType::TYPE_STRING);
    $sheet->setCellValueExplicit($coverNameCol . $r, $coverName, DataType::TYPE_STRING);
}

// Save new workbook
$outDir = dirname($outPath);
if (!is_dir($outDir)) {
    @mkdir($outDir, 0777, true);
}
$writer = new Xlsx($ss);
$writer->save($outPath);

// Write reports
$unmatchedPath = preg_replace('/\\.xlsx$/i', '', $outPath) . '_unmatched_titles.tsv';
$ambiguousPath = preg_replace('/\\.xlsx$/i', '', $outPath) . '_ambiguous_titles.tsv';

if (is_string($unmatchedPath)) {
    $fh = @fopen($unmatchedPath, 'w');
    if ($fh) {
        fwrite($fh, "row\ttitle\n");
        foreach ($unmatchedRows as $u) {
            $safeTitle = str_replace(["\t", "\r", "\n"], [' ', ' ', ' '], (string)$u['title']);
            fwrite($fh, (string)$u['row'] . "\t" . $safeTitle . "\n");
        }
        fclose($fh);
    }
}

if (is_string($ambiguousPath)) {
    $fh = @fopen($ambiguousPath, 'w');
    if ($fh) {
        fwrite($fh, "row\ttitle\tcandidates\n");
        foreach ($ambiguousRows as $a) {
            $safeTitle = str_replace(["\t", "\r", "\n"], [' ', ' ', ' '], (string)$a['title']);
            $safeCand = str_replace(["\t", "\r", "\n"], [' ', ' ', ' '], (string)$a['candidates']);
            fwrite($fh, (string)$a['row'] . "\t" . $safeTitle . "\t" . $safeCand . "\n");
        }
        fclose($fh);
    }
}

echo "Input: {$excelPath}\n";
echo "Sheet: {$sheetName}\n";
echo "Rows scanned: {$highestRow}\n";
echo "Mapping keys: " . count($mapping) . "\n";
echo "Added columns: {$hasImageCol}=has_image, {$coverNameCol}=cover_name\n";
echo "Matched (has_image=1): {$matched}\n";
echo "Unmatched titles: {$unmatched}\n";
echo "Ambiguous title matches: {$ambiguous}\n";
echo "Matched but file missing: {$missingFile}\n";
echo "Wrote: {$outPath}\n";
if (is_string($unmatchedPath)) {
    echo "Unmatched report: {$unmatchedPath}\n";
}
if (is_string($ambiguousPath)) {
    echo "Ambiguous report: {$ambiguousPath}\n";
}

