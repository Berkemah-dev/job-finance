<?php

namespace App\Http\Controllers;

use App\Models\LclRate;
use App\Services\CalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalculatorController extends Controller
{
    public function __construct(private CalculationService $calc) {}

    public function index()
    {
        return view('calculators.index');
    }

    public function volumeWeight()
    {
        return view('calculators.volume-weight');
    }

    public function lcl()
    {
        return view('calculators.lcl');
    }

    public function tax()
    {
        return view('calculators.tax');
    }

    public function packages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'packages' => ['required', 'array', 'min:1', 'max:50'],
            'packages.*.qty' => ['required', 'integer', 'min:1', 'max:999999'],
            'packages.*.length' => ['required', 'numeric', 'min:0', 'max:999999'],
            'packages.*.width' => ['required', 'numeric', 'min:0', 'max:999999'],
            'packages.*.height' => ['required', 'numeric', 'min:0', 'max:999999'],
            'packages.*.gross_weight' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        return response()->json($this->calc->packageTotals($data['packages']));
    }

    public function lclApi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lcl_rate_id' => ['nullable', 'integer', 'exists:lcl_rates,id'],
            'packages' => ['required', 'array', 'min:1', 'max:50'],
            'packages.*.qty' => ['required', 'integer', 'min:1', 'max:999999'],
            'packages.*.length' => ['required', 'numeric', 'min:0', 'max:999999'],
            'packages.*.width' => ['required', 'numeric', 'min:0', 'max:999999'],
            'packages.*.height' => ['required', 'numeric', 'min:0', 'max:999999'],
            'packages.*.gross_weight' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'ocean_freight_rate' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'gri_rate' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'cfs_rate' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'cfs_min_wm' => ['required', 'numeric', 'min:0', 'max:999999'],
            'others_per_set' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'mechanic_rate' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'mechanic_min_wm' => ['required', 'numeric', 'min:0', 'max:999999'],
            'administration' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
        ]);

        if (! empty($data['lcl_rate_id'])) {
            $rate = LclRate::query()->where('is_active', true)->findOrFail($data['lcl_rate_id']);
            $data = array_merge($data, [
                'ocean_freight_rate' => $rate->ocean_freight_rate,
                'gri_rate' => $rate->gri_rate,
                'cfs_rate' => $rate->cfs_rate,
                'cfs_min_wm' => $rate->cfs_min_wm,
                'others_per_set' => $rate->others_per_set,
                'mechanic_rate' => $rate->mechanic_rate,
                'mechanic_min_wm' => $rate->mechanic_min_wm,
                'administration' => $rate->administration,
            ]);
        }

        return response()->json($this->calc->lclEstimate($data['packages'], $data));
    }

    public function taxApi(Request $request): JsonResponse
    {
        if ($request->has('rate') && ! $request->has('import_duty_rate')) {
            $data = $request->validate([
                'base_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999999.99'],
                'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            ]);

            $base = (float) $data['base_amount'];
            $rate = (float) $data['rate'];
            $tax = round($base * ($rate / 100), 2);
            $total = round($base + $tax, 2);

            return response()->json([
                'base' => number_format($base, 2, '.', ''),
                'rate' => $rate,
                'tax' => number_format($tax, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
            ]);
        }

        $data = $request->validate([
            'base_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999999.99'],
            'import_duty_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'pph_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'withholding_mode' => ['nullable', 'in:api,non_api,manual'],
            'withholding_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $base = (float) $data['base_amount'];
        $importDuty = round($base * ((float) $data['import_duty_rate'] / 100), 2);
        $taxBase = $base + $importDuty;
        $vat = round($taxBase * ((float) $data['vat_rate'] / 100), 2);
        $withholdingRate = array_key_exists('pph_rate', $data) && $data['pph_rate'] !== null
            ? (float) $data['pph_rate']
            : match ($data['withholding_mode'] ?? 'manual') {
                'api' => 2.5, 'non_api' => 7, default => (float) ($data['withholding_rate'] ?? 0),
            };
        $withholding = round($taxBase * ($withholdingRate / 100), 2);
        return response()->json(['base' => $base, 'import_duty' => $importDuty, 'tax_base' => $taxBase, 'vat' => $vat, 'vat_rate' => (float) $data['vat_rate'], 'withholding' => $withholding, 'withholding_rate' => $withholdingRate, 'total_tax' => round($importDuty + $vat + $withholding, 2), 'total' => round($base + $importDuty + $vat + $withholding, 2)]);
    }
}
