<?php

namespace App\Http\Controllers;

use App\Services\PricingService;
use App\Models\ContainerUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PricingSuggestionController extends Controller
{
    public function __construct(private PricingService $pricing) {}

    public function suggestTrucking(Request $request): JsonResponse
    {
        Gate::authorize('quotations.manage');
        $data = $request->validate([
            'port_origin' => ['required', 'string', 'max:120'],
            'destination' => ['required', 'string', 'max:120'],
            'container_type' => ['required', 'string', Rule::in(ContainerUnit::allowedKeys())],
            'overweight' => ['nullable', 'boolean'],
            'vendor_id' => ['nullable', 'integer', 'min:1'],
            'date' => ['nullable', 'date'],
        ]);

        $suggestion = $this->pricing->suggestTrucking(
            $data['port_origin'],
            $data['destination'],
            $data['container_type'],
            (bool) ($data['overweight'] ?? false),
            $data['vendor_id'] ?? null,
            $data['date'] ?? null
        );

        return response()->json($suggestion);
    }
}
