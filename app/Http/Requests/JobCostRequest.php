<?php

namespace App\Http\Requests;

use App\Enums\CostType;
use App\Models\JobCost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('cost') ? $this->user()->can('update', $this->route('cost')) : $this->user()->can('create', [JobCost::class, $this->route('job')]);
    }

    public function rules(): array
    {
        $money = ['required', 'regex:/^\\d{1,9}(\\.\\d{1,2})?$/'];

        return ['job_version' => ['required', 'integer', 'min:0'], 'lock_version' => [$this->route('cost') ? 'required' : 'nullable', 'integer', 'min:0'],
            'description' => ['required', 'string', 'max:255'], 'type' => ['required', Rule::enum(CostType::class)],
            'cost_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$this->route('job')->job_date->format('Y-m-d')],
            'quantity' => ['required', 'regex:/^\\d{1,6}(\\.\\d{1,2})?$/', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:30'], 'unit_cost' => $money, 'unit_price' => $money,
            'payee' => ['nullable', 'string', 'max:255'], 'reference' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:2000']];
    }

    public function attributes(): array
    {
        return ['description' => 'uraian biaya', 'type' => 'jenis biaya', 'cost_date' => 'tanggal biaya', 'quantity' => 'jumlah', 'unit' => 'satuan', 'unit_cost' => 'modal per unit', 'unit_price' => 'nilai jual per unit', 'payee' => 'penerima/vendor', 'reference' => 'nomor bukti', 'notes' => 'catatan', 'job_version' => 'versi job', 'lock_version' => 'versi biaya'];
    }
}
