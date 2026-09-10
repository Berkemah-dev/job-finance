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
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        return response()->json($this->calc->tax($data['base_amount'], $data['rate']));
    }
}
