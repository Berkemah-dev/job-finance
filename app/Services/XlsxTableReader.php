<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class XlsxTableReader
{
    /** @return array<int, array<int, string>> */
    public function read(UploadedFile $file): array
    {
        if (strtolower($file->getClientOriginalExtension()) === 'csv') {
            return $this->readCsv($file->getRealPath());
        }

        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['file' => 'Server belum memiliki ekstensi ZIP untuk membaca file XLSX.']);
        }

        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages(['file' => 'File Excel tidak dapat dibaca. Gunakan format .xlsx.']);
        }
        try {
            $shared = $this->sharedStrings($zip->getFromName('xl/sharedStrings.xml') ?: '');
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            if (! $sheet) {
                throw ValidationException::withMessages(['file' => 'Sheet pertama tidak ditemukan pada file Excel.']);
            }

            $xml = simplexml_load_string($sheet);
            $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rows = [];
            foreach ($xml->xpath('//x:sheetData/x:row') ?: [] as $row) {
                $cells = [];
                foreach ($row->xpath('./x:c') ?: [] as $cell) {
                    $reference = (string) $cell['r'];
                    $column = $this->columnIndex(preg_replace('/\d+/', '', $reference));
                    $type = (string) $cell['t'];
                    $value = (string) ($cell->v ?? '');
                    if ($type === 's') {
                        $value = $shared[(int) $value] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = (string) ($cell->is->t ?? '');
                    }
                    $cells[$column] = trim($value);
                }
                if ($cells) {
                    ksort($cells);
                    $rows[] = $cells;
                }
            }
            return $rows;
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, string> */
    private function sharedStrings(string $content): array
    {
        if ($content === '') return [];
        $xml = simplexml_load_string($content);
        $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        return array_map(fn ($node) => trim((string) $node), $xml->xpath('//x:si') ?: []);
    }

    /** @return array<int, array<int, string>> */
    private function readCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'rb');
        while (($row = fgetcsv($handle)) !== false) $rows[] = array_map(fn ($value) => trim((string) $value), $row);
        fclose($handle);
        return $rows;
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) $index = $index * 26 + (ord($letter) - 64);
        return $index - 1;
    }
}
