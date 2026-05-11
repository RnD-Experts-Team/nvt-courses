<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use ZipArchive;

class XlsxExportService
{
    /**
     * Export tabular data to a single-sheet XLSX file.
     */
    public function export(string $filename, array $headers, array $rows)
    {
        $baseTempPath = storage_path('app/tmp/xlsx_exports');
        File::ensureDirectoryExists($baseTempPath);

        $unique = uniqid('quiz_export_', true);
        $workingDir = $baseTempPath . DIRECTORY_SEPARATOR . $unique;
        $xlsxPath = $baseTempPath . DIRECTORY_SEPARATOR . $unique . '.xlsx';

        File::ensureDirectoryExists($workingDir . DIRECTORY_SEPARATOR . '_rels');
        File::ensureDirectoryExists($workingDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . '_rels');
        File::ensureDirectoryExists($workingDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'worksheets');

        $this->writeCoreFiles($workingDir);
        $this->writeSheetFile($workingDir, $headers, $rows);

        $zip = new ZipArchive();
        if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            File::deleteDirectory($workingDir);
            abort(500, 'Unable to create XLSX file.');
        }

        $this->zipDirectory($zip, $workingDir, '');
        $zip->close();

        File::deleteDirectory($workingDir);

        return response()->download($xlsxPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function writeCoreFiles(string $workingDir): void
    {
        File::put($workingDir . DIRECTORY_SEPARATOR . '[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>'
        );

        File::put($workingDir . DIRECTORY_SEPARATOR . '_rels' . DIRECTORY_SEPARATOR . '.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>'
        );

        File::put($workingDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            . '<sheet name="Quiz Report" sheetId="1" r:id="rId1"/>'
            . '</sheets>'
            . '</workbook>'
        );

        File::put($workingDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . '_rels' . DIRECTORY_SEPARATOR . 'workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>'
        );

        File::put($workingDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>'
        );
    }

    private function writeSheetFile(string $workingDir, array $headers, array $rows): void
    {
        $path = $workingDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'worksheets' . DIRECTORY_SEPARATOR . 'sheet1.xml';
        $handle = fopen($path, 'w');

        if ($handle === false) {
            abort(500, 'Unable to write worksheet.');
        }

        fwrite($handle,
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>'
        );

        $rowNumber = 1;
        $this->writeRow($handle, $rowNumber, array_values($headers));
        $rowNumber++;

        foreach ($rows as $row) {
            $this->writeRow($handle, $rowNumber, array_values($row));
            $rowNumber++;
        }

        fwrite($handle, '</sheetData></worksheet>');
        fclose($handle);
    }

    private function writeRow($handle, int $rowNumber, array $values): void
    {
        fwrite($handle, '<row r="' . $rowNumber . '">');

        foreach ($values as $index => $value) {
            $cellRef = $this->columnLetters($index + 1) . $rowNumber;
            fwrite($handle, $this->buildCellXml($cellRef, $value));
        }

        fwrite($handle, '</row>');
    }

    private function buildCellXml(string $cellRef, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '<c r="' . $cellRef . '"/>';
        }

        if (is_bool($value)) {
            return '<c r="' . $cellRef . '" t="b"><v>' . ($value ? '1' : '0') . '</v></c>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="' . $cellRef . '"><v>' . $value . '</v></c>';
        }

        $text = $this->escapeXml((string) $value);
        return '<c r="' . $cellRef . '" t="inlineStr"><is><t xml:space="preserve">' . $text . '</t></is></c>';
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function columnLetters(int $index): string
    {
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = intdiv($index - 1, 26);
        }

        return $letters;
    }

    private function zipDirectory(ZipArchive $zip, string $dir, string $prefix): void
    {
        $items = File::allFiles($dir);
        foreach ($items as $file) {
            $fullPath = $file->getRealPath();
            if ($fullPath === false) {
                continue;
            }

            $relativePath = ltrim(str_replace($dir, '', $fullPath), DIRECTORY_SEPARATOR);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            $zip->addFile($fullPath, $prefix . $relativePath);
        }
    }
}
