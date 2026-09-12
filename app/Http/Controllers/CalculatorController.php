<?php

namespace App\Http\Controllers;

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
            'packages' => ['required', 'array', 'min:1', 'max:50'],
            'packages.*.qty' => ['required', 'integer', 'min:1', 'max:999999'],
            'packages.*.length' => ['required', 'numeric', 'min:0', 'max:999999'],
            'packages.*.width' => ['required', 'numeric', 'min:0', 'max:999999'],
            'packages.*.height' => ['required', 'numeric', 'min:0', 'max:999999'],
            'packages.*.gross_weight' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'rate_per_cbm' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
        ]);

        return response()->json($this->calc->lcl($data['packages'], $data['rate_per_cbm'] ?? null));
    }

    public function taxApi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'base_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999999.99'],
            'import_duty_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'withholding_mode' => ['required', 'in:api,non_api,manual'],
            'withholding_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $base = (float) $data['base_amount'];
        $importDuty = round($base * ((float) $data['import_duty_rate'] / 100), 2);
        $taxBase = $base + $importDuty;
        $vat = round($taxBase * ((float) $data['vat_rate'] / 100), 2);
        $withholdingRate = match ($data['withholding_mode']) {
            'api' => 2.5, 'non_api' => 7, default => (float) ($data['withholding_rate'] ?? 0),
        };
        $withholding = round($taxBase * ($withholdingRate / 100), 2);
        return response()->json(['base' => $base, 'import_duty' => $importDuty, 'tax_base' => $taxBase, 'vat' => $vat, 'vat_rate' => (float) $data['vat_rate'], 'withholding' => $withholding, 'withholding_rate' => $withholdingRate, 'total_tax' => round($importDuty + $vat + $withholding, 2), 'total' => round($base + $importDuty + $vat + $withholding, 2)]);
    }
}
