<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BookBulkImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

class BookImportController extends Controller
{
    public function index()
    {
        return Voyager::view('voyager::book-import.index');
    }

    public function import(Request $request, BookBulkImportService $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:xlsx,xls,csv,txt'],
        ]);

        $parsed = $importer->parse($request->file('file'));

        if (!empty($parsed['warnings'])) {
            return back()
                ->withInput()
                ->with('book_import_warnings', $parsed['warnings'])
                ->with('book_import_result', null);
        }

        $result = $importer->import($parsed['rows']);

        $skippedDownloads = $this->exportSkippedRowsIfAny($result);
        // Avoid flashing large payloads to session (can be thousands of rows)
        if (array_key_exists('skipped_rows', $result)) {
            unset($result['skipped_rows']);
        }

        return back()
            ->with('book_import_warnings', $parsed['warnings'])
            ->with('book_import_result', $result)
            ->with('book_import_skipped_downloads', $skippedDownloads);
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, string>|null
     */
    private function exportSkippedRowsIfAny(array $result)
    {
        $rows = (array)($result['skipped_rows'] ?? []);
        if (count($rows) === 0) {
            return null;
        }

        $exportRows = [];
        $exportRows[] = [
            'Row',
            'Reason',
            'Existing Book ID',
            'Title',
            'Author',
            'Publisher',
            'Category',
            'Price',
            'ISBN',
            'Year',
            'Description',
            'Available',
            'Image',
            'Internal Code',
        ];

        foreach ($rows as $r) {
            $exportRows[] = [
                (int)($r['row'] ?? 0),
                (string)($r['reason'] ?? 'Skipped'),
                (int)($r['existing_book_id'] ?? 0),
                (string)($r['title'] ?? ''),
                (string)($r['author'] ?? ''),
                (string)($r['publisher'] ?? ''),
                (string)($r['category'] ?? ''),
                (string)($r['price'] ?? 0),
                (string)($r['isbn'] ?? ''),
                (string)($r['year'] ?? ''),
                (string)($r['description'] ?? ''),
                (string)($r['available'] ?? ''),
                (string)($r['image'] ?? ''),
                (string)($r['internal_code'] ?? ''),
            ];
        }

        $uuid = (string) Str::uuid();
        $base = 'import-results/skipped-books-' . $uuid;

        // CSV
        $csvPath = $base . '.csv';
        $csvContent = $this->toCsv($exportRows);
        Storage::disk('public')->put($csvPath, $csvContent);

        $downloads = [
            'csv' => Storage::disk('public')->url($csvPath),
        ];

        // XLSX (if available)
        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $xlsxPath = $base . '.xlsx';
            $tmp = tempnam(sys_get_temp_dir(), 'skipped-books-');
            if ($tmp) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Skipped');
                $sheet->fromArray($exportRows, null, 'A1', true);
                $sheet->freezePane('A2');
                $sheet->getStyle('A1:N1')->getFont()->setBold(true);
                foreach (range('A', 'N') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save($tmp);

                Storage::disk('public')->put($xlsxPath, file_get_contents($tmp));
                @unlink($tmp);

                $downloads['xlsx'] = Storage::disk('public')->url($xlsxPath);
            }
        }

        return $downloads;
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function toCsv(array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv ?: '';
    }
}

