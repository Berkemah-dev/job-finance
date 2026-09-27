<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class XlsxTableReader
{
    /** @return array<int, array<int, string>> */
    public function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === 'csv') {
            return $this->readCsv($file->getRealPath());
        }

        if (! class_exists(IOFactory::class)) {
            throw ValidationException::withMessages(['file' => 'Pembaca file Excel belum terpasang di server. Jalankan composer install, lalu upload ulang file .xls atau .xlsx.']);
        }

        try {
            $worksheet = IOFactory::load($file->getRealPath())->getSheet(0);
            return array_map(
                fn (array $row) => array_map(fn ($value) => trim((string) $value), $row),
                $worksheet->toArray('', true, true, false)
            );
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'File Excel tidak dapat dibaca. Gunakan file .xls, .xlsx, atau .csv yang valid.']);
        }
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
}
