<?php
/**
 * Append DB books (by ID list) to an existing Excel workbook, matching the workbook's
 * existing DB-export column layout and keeping categories within the workbook's limited set.
 *
 * This script DOES NOT modify the original workbook; it writes a new XLSX output file.
 *
 * Expected workbook (Sheet1) layout (no header row):
 *   A: normalized_title
 *   B: normalized_author
 *   C: title (original)
 *   D: author (original)
 *   E: publisher
 *   F: category (must be within limited set already present in workbook)
 *   G: ISBN
 *   H: internal_code
 *   (Any columns beyond H are preserved and left blank for appended rows)
 *
 * Usage (from waha-darin/):
 *   php scripts/append_db_books_not_in_excel_to_workbook.php \
 *     --excel="storage/app/public/book-covers-by-title/SicherheitsKopie von Darbooks_final_books.xlsx" \
 *     --missing="storage/app/public/book-covers-by-title/_db_books_not_in_excel_with_author.tsv" \
 *     --out="storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910.xlsx"
 */
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$excelRel = 'storage/app/public/book-covers-by-title/SicherheitsKopie von Darbooks_final_books.xlsx';
$missingTsvRel = 'storage/app/public/book-covers-by-title/_db_books_not_in_excel_with_author.tsv';
$outRel = 'storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910.xlsx';
$sheetName = null; // default: active sheet

foreach ($argv as $arg) {
    if (strpos($arg, '--excel=') === 0) {
        $excelRel = (string)substr($arg, strlen('--excel='));
    } elseif (strpos($arg, '--missing=') === 0) {
        $missingTsvRel = (string)substr($arg, strlen('--missing='));
    } elseif (strpos($arg, '--out=') === 0) {
        $outRel = (string)substr($arg, strlen('--out='));
    } elseif (strpos($arg, '--sheet=') === 0) {
        $sheetName = (string)substr($arg, strlen('--sheet='));
    }
}

$basePath = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
$excelPath = $excelRel;
$missingPath = $missingTsvRel;
$outPath = $outRel;

foreach (['excelPath' => &$excelPath, 'missingPath' => &$missingPath, 'outPath' => &$outPath] as &$ref) {
    if (!preg_match('#^/#', $ref)) {
        $ref = $basePath . '/' . ltrim($ref, '/');
    }
}

if (!is_file($excelPath)) {
    fwrite(STDERR, "Excel not found: {$excelPath}\n");
    exit(1);
}
if (!is_file($missingPath)) {
    fwrite(STDERR, "Missing TSV not found: {$missingPath}\n");
    exit(1);
}

/**
 * Similar to the earlier Arabic-friendly normalization, but we keep punctuation
 * (to mirror the workbook's A/B normalized columns which still contain commas/parens).
 */
function normalizeForWorkbookKey(string $s): string
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
        '،' => ',',
        '؛' => ';',
    ];
    $s = strtr($s, $map);

    // remove Arabic diacritics
    $s = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $s) ?? $s;

    // collapse whitespace
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    $s = trim($s);
    return $s;
}

function normalizeSpaces(string $s): string
{
    $s = preg_replace('/\s+/u', ' ', trim($s));
    return $s ?? '';
}

/**
 * Safe normalization for category matching:
 * - normalize whitespace
 * - normalize Arabic letter variants
 * - remove diacritics/tatweel
 * - replace punctuation with spaces
 * - remove leading "ال" from words
 */
function normalizeCategoryForMatch(string $s): string
{
    $s = normalizeSpaces($s);
    if ($s === '') {
        return '';
    }

    $s = preg_replace('/[\x{0640}\x{064B}-\x{065F}\x{0670}]/u', '', $s) ?? $s;
    $s = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $s);
    $s = str_replace(['ى'], 'ي', $s);
    $s = str_replace(['ة'], 'ه', $s);
    $s = str_replace(['،', ',', ';', '؛', '/', '\\', '-', '–', '—', '|', '・'], ' ', $s);
    $s = normalizeSpaces($s);

    $words = explode(' ', $s);
    $words = array_map(function ($w) {
        $w = trim($w);
        if ($w === '') {
            return '';
        }
        if (mb_substr($w, 0, 3, 'UTF-8') === 'وال' && mb_strlen($w, 'UTF-8') > 3) {
            return 'و' . mb_substr($w, 3, null, 'UTF-8');
        }
        if (mb_substr($w, 0, 3, 'UTF-8') === 'بال' && mb_strlen($w, 'UTF-8') > 3) {
            return 'ب' . mb_substr($w, 3, null, 'UTF-8');
        }
        if (mb_substr($w, 0, 3, 'UTF-8') === 'كال' && mb_strlen($w, 'UTF-8') > 3) {
            return 'ك' . mb_substr($w, 3, null, 'UTF-8');
        }
        if (mb_substr($w, 0, 3, 'UTF-8') === 'فال' && mb_strlen($w, 'UTF-8') > 3) {
            return 'ف' . mb_substr($w, 3, null, 'UTF-8');
        }
        if (mb_substr($w, 0, 2, 'UTF-8') === 'ال' && mb_strlen($w, 'UTF-8') > 2) {
            return mb_substr($w, 2, null, 'UTF-8');
        }
        return $w;
    }, $words);

    $words = array_values(array_filter($words, function ($w) {
        return $w !== '';
    }));
    return normalizeSpaces(implode(' ', $words));
}

/**
 * Read missing TSV and return ordered unique list of book IDs.
 *
 * @return array<int, int>
 */
function readBookIdsFromMissingTsv(string $path): array
{
    $fh = fopen($path, 'r');
    if (!$fh) {
        throw new RuntimeException("Unable to open TSV: {$path}");
    }

    $header = fgetcsv($fh, 0, "\t");
    if (!$header) {
        fclose($fh);
        throw new RuntimeException("TSV missing header: {$path}");
    }
    $idx = array_search('book_id', $header, true);
    if ($idx === false) {
        fclose($fh);
        throw new RuntimeException("TSV missing book_id column: {$path}");
    }

    $out = [];
    $seen = [];
    while (($row = fgetcsv($fh, 0, "\t")) !== false) {
        $raw = trim((string)($row[$idx] ?? ''));
        if ($raw === '' || !ctype_digit($raw)) {
            continue;
        }
        $id = (int)$raw;
        if ($id <= 0 || isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $out[] = $id;
    }
    fclose($fh);
    return $out;
}

$bookIds = readBookIdsFromMissingTsv($missingPath);
if (count($bookIds) === 0) {
    fwrite(STDERR, "No book IDs found in missing TSV.\n");
    exit(1);
}

// Load workbook + sheet
$ss = IOFactory::load($excelPath);
$sheet = $sheetName ? $ss->getSheetByName($sheetName) : $ss->getActiveSheet();
if (!$sheet) {
    fwrite(STDERR, "Sheet not found.\n");
    exit(1);
}

// Determine highest data column/row (for preserving column count)
$highestCol = $sheet->getHighestDataColumn();
$highestColIndex = Coordinate::columnIndexFromString($highestCol);
$highestRow = $sheet->getHighestDataRow();

// Find a more reliable "last data row": scan from bottom up for any non-empty A..H
$lastDataRow = 0;
for ($r = $highestRow; $r >= 1; $r--) {
    $hasAny = false;
    foreach (range('A', 'H') as $col) {
        $v = $sheet->getCell($col . $r)->getValue();
        if (trim((string)$v) !== '') {
            $hasAny = true;
            break;
        }
    }
    if ($hasAny) {
        $lastDataRow = $r;
        break;
    }
}
if ($lastDataRow <= 0) {
    fwrite(STDERR, "Workbook appears empty (no data found in A..H).\n");
    exit(1);
}

// Build the workbook's limited category set from column F
$workbookCategories = []; // name => true
$workbookCategoriesByNorm = []; // normalized => canonical name
for ($r = 1; $r <= $lastDataRow; $r++) {
    $cat = normalizeSpaces((string)$sheet->getCell('F' . $r)->getValue());
    if ($cat === '') {
        continue;
    }
    if (strtolower($cat) === 'category') {
        continue;
    }
    $workbookCategories[$cat] = true;
    $workbookCategoriesByNorm[normalizeCategoryForMatch($cat)] = $cat;
}

// Config aliases (optional) - used only as a hint, still constrained to workbook category set.
$aliases = [];
$cfg = $basePath . '/config/book_import.php';
if (is_file($cfg)) {
    $conf = require $cfg;
    if (is_array($conf) && isset($conf['category_aliases']) && is_array($conf['category_aliases'])) {
        $aliases = $conf['category_aliases'];
    }
}

// Bootstrap Laravel for DB access
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Book;

// Fetch all books by ID (keep map for ordered output)
$books = Book::query()
    ->whereIn('id', $bookIds)
    ->with([
        'author:id,name',
        'publisher:id,name',
        'categories:id,name',
    ])
    ->get(['id', 'title', 'author_id', 'publisher_id', 'ISBN', 'internal_code']);

$byId = [];
foreach ($books as $b) {
    $byId[(int)$b->id] = $b;
}

$startRow = $lastDataRow + 1;
$written = 0;
$missingInDb = 0;
$categoryBlank = 0;

// Optional: record unmapped categories per book
$unmappedReport = [];

foreach ($bookIds as $id) {
    $b = $byId[$id] ?? null;
    if (!$b) {
        $missingInDb++;
        continue;
    }

    $title = normalizeSpaces((string)($b->title ?? ''));
    $authorName = normalizeSpaces((string)optional($b->author)->name);
    $publisherName = normalizeSpaces((string)optional($b->publisher)->name);
    $isbn = normalizeSpaces((string)($b->ISBN ?? ''));
    $internal = normalizeSpaces((string)($b->internal_code ?? ''));

    $normTitle = normalizeForWorkbookKey($title);
    $normAuthor = normalizeForWorkbookKey($authorName);

    // Category mapping: try DB category names in order, then alias, then normalized match.
    $mappedCategory = '';
    $dbCats = [];
    foreach (($b->categories ?? []) as $cat) {
        $name = normalizeSpaces((string)($cat->name ?? ''));
        if ($name !== '') {
            $dbCats[] = $name;
        }
    }

    foreach ($dbCats as $raw) {
        if (isset($workbookCategories[$raw])) {
            $mappedCategory = $raw;
            break;
        }
        $aliasTo = $aliases[$raw] ?? null;
        if (is_string($aliasTo) && $aliasTo !== '' && isset($workbookCategories[$aliasTo])) {
            $mappedCategory = $aliasTo;
            break;
        }

        $norm = normalizeCategoryForMatch($raw);
        if ($norm !== '' && isset($workbookCategoriesByNorm[$norm])) {
            $mappedCategory = $workbookCategoriesByNorm[$norm];
            break;
        }

        if (is_string($aliasTo) && $aliasTo !== '') {
            $norm2 = normalizeCategoryForMatch($aliasTo);
            if ($norm2 !== '' && isset($workbookCategoriesByNorm[$norm2])) {
                $mappedCategory = $workbookCategoriesByNorm[$norm2];
                break;
            }
        }
    }

    if ($mappedCategory === '') {
        $categoryBlank++;
        $unmappedReport[] = [
            'book_id' => $id,
            'book_title' => $title,
            'db_categories' => implode(' | ', $dbCats),
        ];
    }

    $row = $startRow + $written;

    // A..H
    $sheet->setCellValueExplicit('A' . $row, $normTitle, DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('B' . $row, $normAuthor, DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('C' . $row, $title, DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('D' . $row, $authorName, DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('E' . $row, $publisherName, DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('F' . $row, $mappedCategory, DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('G' . $row, $isbn, DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('H' . $row, $internal, DataType::TYPE_STRING);

    // Clear remaining columns up to existing highest data column, if any (avoid inheriting values)
    if ($highestColIndex > 8) {
        for ($ci = 9; $ci <= $highestColIndex; $ci++) {
            $colLetter = Coordinate::stringFromColumnIndex($ci);
            $sheet->setCellValueExplicit($colLetter . $row, '', DataType::TYPE_STRING);
        }
    }

    $written++;
}

// Ensure output directory exists
$outDir = dirname($outPath);
if (!is_dir($outDir)) {
    @mkdir($outDir, 0777, true);
}

$writer = new Xlsx($ss);
$writer->save($outPath);

// Write unmapped category report (TSV) next to output (best-effort)
$unmappedPath = preg_replace('/\\.xlsx$/i', '', $outPath) . '_unmapped_categories.tsv';
if (is_string($unmappedPath) && !empty($unmappedReport)) {
    $fh = @fopen($unmappedPath, 'w');
    if ($fh) {
        fwrite($fh, "book_id\tbook_title\tdb_categories\n");
        foreach ($unmappedReport as $r) {
            $safeTitle = str_replace(["\t", "\r", "\n"], ['\\t', ' ', ' '], (string)($r['book_title'] ?? ''));
            $safeCats = str_replace(["\t", "\r", "\n"], ['\\t', ' ', ' '], (string)($r['db_categories'] ?? ''));
            fwrite($fh, (string)$r['book_id'] . "\t" . $safeTitle . "\t" . $safeCats . "\n");
        }
        fclose($fh);
    }
}

$oldRowCount = $lastDataRow;
$newRowCount = $lastDataRow + $written;

echo "Workbook: {$excelPath}\n";
echo "Sheet: " . $sheet->getTitle() . "\n";
echo "Last data row (before): {$oldRowCount}\n";
echo "Rows appended: {$written}\n";
echo "Last data row (after): {$newRowCount}\n";
echo "Books listed in TSV but missing in DB: {$missingInDb}\n";
echo "Appended rows with blank category (unmappable): {$categoryBlank}\n";
echo "Wrote: {$outPath}\n";
if (!empty($unmappedReport) && is_string($unmappedPath)) {
    echo "Unmapped category report: {$unmappedPath}\n";
}

