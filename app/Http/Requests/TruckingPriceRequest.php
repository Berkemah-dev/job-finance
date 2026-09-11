<?php

namespace App\Http\Requests;

use App\Models\TruckingPrice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TruckingPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pricing.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['overweight' => $this->boolean('overweight')]);
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        return [
            'port_origin' => ['required', 'string', 'max:120'],
            'destination' => ['required', 'string', 'max:120'],
            'overweight' => ['boolean'],
            'container_type' => ['required', Rule::in(array_keys(config('operations.container_types')))],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'selling_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'currency' => ['required', Rule::in(array_keys(config('operations.currencies')))],
            'effective_date' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'is_active' => ['boolean'],
            'lock_version' => [$this->isMethod('PUT') ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn () => $this->assertNoOverlap($validator));
    }

    /**
     * Enforce: satu tarif aktif untuk kombinasi rute+overweight+kontainer+vendor
     * tidak boleh tumpang-tindih tanggal berlakunya (effective_until kosong = tanpa batas akhir).
     */
    private function assertNoOverlap(Validator $validator): void
    {
        $data = $validator->validated();
        if (($data['is_active'] ?? true) !== true || $validator->errors()->isNotEmpty()) {
            return;
        }
        $start = $data['effective_date'];
        $end = $data['effective_until'] ?? null;
        $query = TruckingPrice::query()
            ->where('is_active', true)
            ->where('port_origin', $data['port_origin'])
            ->where('destination', $data['destination'])
            ->where('container_type', $data['container_type'])
            ->where('overweight', filter_var($data['overweight'], FILTER_VALIDATE_BOOL))
            ->where(fn ($q) => $q->whereNull('vendor_id')->orWhere('vendor_id', $data['vendor_id'] ?? null))
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', $start));
        if ($end !== null) {
            $query->where('effective_date', '<=', $end);
        }
        if ($this->route('truckingPrice')) {
            $query->whereKeyNot((int) $this->route('truckingPrice')->id);
        }
        if ($query->exists()) {
            $validator->errors()->add('effective_date', 'Sudah ada tarif aktif untuk rute/overweight/kontainer/vendor yang sama pada periode tanggal ini.');
        }
    }

    public function attributes(): array
    {
        return ['port_origin' => 'pelabuhan asal', 'destination' => 'tujuan', 'overweight' => 'overweight', 'container_type' => 'tipe kontainer', 'price' => 'harga', 'currency' => 'mata uang', 'effective_date' => 'tanggal berlaku', 'effective_until' => 'berlaku sampai', 'is_active' => 'status aktif', 'lock_version' => 'versi data'];
    }
}
