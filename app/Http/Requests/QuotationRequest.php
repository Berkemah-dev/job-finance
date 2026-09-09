<?php

namespace App\Http\Requests;

use App\Enums\CostType;
use App\Models\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('quotation') ? $this->user()->can('update', $this->route('quotation')) : $this->user()->can('create', Quotation::class);
    }

    public function rules(): array
    {
        $money = ['required', 'regex:/^\\d{1,9}(\\.\\d{1,2})?$/'];

        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'subject' => ['required', 'string', 'max:255'], 'quotation_date' => ['required', 'date_format:Y-m-d'],
            'valid_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:quotation_date'], 'notes' => ['nullable', 'string', 'max:5000'],
            'lock_version' => [$this->isMethod('PUT') ? 'required' : 'nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*' => ['required', 'array'],
            'items.*.description' => ['required', 'string', 'max:255'], 'items.*.type' => ['required', Rule::enum(CostType::class)],
            'items.*.unit' => ['required', 'string', 'max:30'], 'items.*.quantity' => ['required', 'regex:/^\\d{1,6}(\\.\\d{1,2})?$/', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => $money, 'items.*.unit_price' => $money,
            'items.*.currency' => ['nullable', 'string', Rule::in(array_keys(config('operations.currencies')))],
            'items.*.exchange_rate' => ['nullable', 'regex:/^\\d{1,9}(\\.\\d{1,2})?$/'],
            'items.*.container_type' => ['nullable', 'string', Rule::in(array_keys(config('operations.container_types')))],
            'items.*.overweight' => ['nullable', 'boolean'],
            'items.*.gross_weight' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.volume' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.pricing_source' => ['nullable', 'string', Rule::in(array_keys(config('operations.pricing_sources')))],
            'items.*.pricing_id' => ['nullable', 'integer', 'min:1'],
            'items.*.port_origin' => ['nullable', 'string', 'max:120'],
            'items.*.destination' => ['nullable', 'string', 'max:120'],
            'items.*.vendor_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['customer_id' => 'customer', 'subject' => 'judul penawaran', 'quotation_date' => 'tanggal quotation', 'valid_until' => 'berlaku sampai', 'items' => 'detail biaya', 'items.*.description' => 'uraian item', 'items.*.type' => 'jenis biaya', 'items.*.quantity' => 'jumlah', 'items.*.unit' => 'satuan', 'items.*.unit_cost' => 'modal per unit', 'items.*.unit_price' => 'nilai jual per unit', 'items.*.currency' => 'mata uang', 'items.*.exchange_rate' => 'kurs', 'items.*.container_type' => 'jenis kontainer', 'items.*.overweight' => 'overweight', 'items.*.gross_weight' => 'berat kotor', 'items.*.volume' => 'volume', 'items.*.pricing_source' => 'sumber tarif', 'items.*.pricing_id' => 'tarif', 'items.*.port_origin' => 'pelabuhan asal', 'items.*.destination' => 'tujuan'];
    }
}
