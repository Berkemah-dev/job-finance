<?php

namespace App\Http\Controllers;

use App\Models\LclRate;
use App\Services\XlsxTableReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LclRateController extends Controller
{
    public function index()
    {
        return view('master.lcl-rates.index', [
            'rates' => LclRate::query()->orderBy('fob_port')->orderBy('subject')->paginate(25),
        ]);
    }

    public function import(Request $request, XlsxTableReader $reader)
    {
        $request->validate(['file' => ['required', 'file', 'max:5120']]);
        $file = $request->file('file');
        if (! in_array(strtolower($file->getClientOriginalExtension()), ['xls', 'xlsx', 'csv'], true)) {
            throw ValidationException::withMessages(['file' => 'Format file harus .xls, .xlsx, atau .csv.']);
        }
        $rows = $reader->read($file);
        $headerRow = collect($rows)->search(function (array $row) {
            $headers = array_map(fn ($value) => $this->header((string) $value), $row);
            return in_array('fob port', $headers, true) || in_array('fob', $headers, true) || in_array('pod', $headers, true);
        });
        if ($headerRow === false || ! isset($rows[$headerRow + 1])) throw ValidationException::withMessages(['file' => 'Header tarif tidak ditemukan. Gunakan kolom FOB Port atau POD.']);

        $headers = array_map(fn ($value) => $this->header((string) $value), $rows[$headerRow]);
        $columns = $this->columns($headers);
        if (! isset($columns['fob_port'])) throw ValidationException::withMessages(['file' => 'Kolom "FOB Port" wajib tersedia.']);

        $saved = 0;
        $skipped = 0;
        DB::transaction(function () use ($rows, $columns, $headerRow, &$saved, &$skipped) {
            foreach (array_slice($rows, $headerRow + 1) as $row) {
                $port = trim((string) $this->cell($row, $columns, 'fob_port'));
                if ($port === '') { $skipped++; continue; }
                $subject = trim((string) $this->cell($row, $columns, 'subject'));
                $customer = trim((string) $this->cell($row, $columns, 'customer'));
                LclRate::updateOrCreate(
                    ['fob_port' => $port, 'subject' => $subject ?: null, 'customer' => $customer ?: null],
                    [
                        'country' => trim((string) $this->cell($row, $columns, 'country')) ?: null,
                        'lead_time_days' => $this->number($this->cell($row, $columns, 'lead_time_days')),
                        'ocean_freight_rate' => $this->number($this->cell($row, $columns, 'ocean_freight_rate')),
                        'gri_rate' => $this->number($this->cell($row, $columns, 'gri_rate')),
                        'cfs_rate' => $this->number($this->cell($row, $columns, 'cfs_rate')),
                        'cfs_min_wm' => $this->minimum($this->cell($row, $columns, 'cfs_rate')),
                        'others_per_set' => $this->number($this->cell($row, $columns, 'others_per_set')),
                        'mechanic_rate' => $this->number($this->cell($row, $columns, 'mechanic_rate')),
                        'mechanic_min_wm' => $this->minimum($this->cell($row, $columns, 'mechanic_rate')),
                        'administration' => $this->number($this->cell($row, $columns, 'administration')),
                        'is_active' => true,
                    ]
                );
                $saved++;
            }
        });

        if (! $saved) throw ValidationException::withMessages(['file' => 'Tidak ada baris dengan FOB Port yang dapat diimpor.']);
        return redirect()->route('lcl-rates.index')->with('success', "$saved tarif LCL berhasil diimpor. $skipped baris kosong dilewati.");
    }

    public function options(): JsonResponse
    {
        return response()->json(LclRate::query()->where('is_active', true)->orderBy('fob_port')->orderBy('subject')->get());
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, ['POD', 'TRANSIT', 'LEAD TIME', 'O/F', 'GRI', 'Cfs (USD)', 'Others', 'Mekanik Charges', 'Adm']);
            fputcsv($stream, ['Chittagong', 'Via Singapore', 15, 'USD 23/W/M', 'USD 19/W/M', '15/W/M (Min 2)', 'USD 50', 'IDR 250,000/W/M (Min 2)', 0]);
            fclose($stream);
        }, 'FOB-LCL-Rates-Template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function columns(array $headers): array
    {
        $aliases = [
            'country' => ['country', 'negara'], 'fob_port' => ['fob port', 'fob', 'pod'],
            'subject' => ['subject', 'transit'], 'customer' => ['customer', 'pelanggan'],
            'lead_time_days' => ['tt', 'tt days', 'lead time', 'lead time days'],
            'ocean_freight_rate' => ['of', 'o f', 'o f usd', 'ocean freight'], 'gri_rate' => ['gri', 'gri usd'],
            'cfs_rate' => ['cfs', 'cfs usd'], 'others_per_set' => ['others', 'other', 'others per set'],
            'mechanic_rate' => ['mekanik charges', 'mekanik', 'mechanic charges', 'mechanic'],
            'administration' => ['adm', 'admin', 'administration'],
        ];
        $columns = [];
        foreach ($aliases as $key => $values) foreach ($values as $value) if (($index = array_search($value, $headers, true)) !== false) { $columns[$key] = $index; break; }
        return $columns;
    }

    private function header(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', strtolower(str_replace(['/', '(', ')'], ' ', $value))));
    }
    private function cell(array $row, array $columns, string $key): string { return isset($columns[$key]) ? (string) ($row[$columns[$key]] ?? '') : ''; }
    private function minimum(string $value): float { return preg_match('/min(?:imum)?\s*(\d+(?:[.,]\d+)?)/i', $value, $m) ? $this->number($m[1]) : 2; }
    private function number(string $value): float
    {
        if (! preg_match('/-?[\d][\d.,]*/', $value, $match)) return 0;
        $number = $match[0];
        if (str_contains($number, ',') && str_contains($number, '.')) $number = str_replace(',', '', $number);
        elseif (str_contains($number, ',')) $number = str_replace(',', '', $number);
        return (float) $number;
    }
}
