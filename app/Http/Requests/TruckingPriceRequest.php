<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'currency' => ['required', Rule::in(array_keys(config('operations.currencies')))],
            'effective_date' => ['required', 'date'],
            'is_active' => ['boolean'],
            'lock_version' => [$this->isMethod('PUT') ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return ['port_origin' => 'pelabuhan asal', 'destination' => 'tujuan', 'overweight' => 'overweight', 'container_type' => 'tipe kontainer', 'price' => 'harga', 'currency' => 'mata uang', 'effective_date' => 'tanggal berlaku', 'is_active' => 'status aktif', 'lock_version' => 'versi data'];
    }
}
