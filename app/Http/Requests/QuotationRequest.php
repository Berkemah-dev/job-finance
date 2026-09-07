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
            'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*' => ['required', 'array:description,type,unit,quantity,unit_cost,unit_price'],
            'items.*.description' => ['required', 'string', 'max:255'], 'items.*.type' => ['required', Rule::enum(CostType::class)],
            'items.*.unit' => ['required', 'string', 'max:30'], 'items.*.quantity' => ['required', 'regex:/^\\d{1,6}(\\.\\d{1,2})?$/', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => $money, 'items.*.unit_price' => $money,
        ];
    }

    public function attributes(): array
    {
        return ['customer_id' => 'customer', 'subject' => 'judul penawaran', 'quotation_date' => 'tanggal quotation', 'valid_until' => 'berlaku sampai', 'items' => 'detail biaya', 'items.*.description' => 'uraian item', 'items.*.type' => 'jenis biaya', 'items.*.quantity' => 'jumlah', 'items.*.unit' => 'satuan', 'items.*.unit_cost' => 'modal per unit', 'items.*.unit_price' => 'nilai jual per unit'];
    }
}
