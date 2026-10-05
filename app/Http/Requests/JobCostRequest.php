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

    protected function prepareForValidation(): void
    {
        if (! $this->filled('cost_category')) {
            $this->merge([
                'cost_category' => $this->input('type') === 'temporary' ? 'reimbursement' : 'payment_request',
            ]);
        }

        if (! $this->filled('currency')) {
            $this->merge(['currency' => 'IDR']);
        }
        $currency = strtoupper((string) $this->input('currency'));
        $this->merge(['currency' => $currency]);

        if ($currency === 'IDR') {
            $this->merge(['exchange_rate' => '1.0000']);
        } else {
            $rawRate = $this->input('exchange_rate');
            if ($rawRate !== null && is_string($rawRate)) {
                $rateVal = trim($rawRate);
                $rateVal = preg_replace('/^Rp\s*/i', '', $rateVal);
                if (str_contains($rateVal, '.') && str_contains($rateVal, ',')) {
                    if (strrpos($rateVal, ',') > strrpos($rateVal, '.')) {
                        $rateVal = str_replace('.', '', $rateVal);
                        $rateVal = str_replace(',', '.', $rateVal);
                    } else {
                        $rateVal = str_replace(',', '', $rateVal);
                    }
                } elseif (str_contains($rateVal, '.')) {
                    if ((substr_count($rateVal, '.') > 1) || preg_match('/^[1-9]\d{0,2}(\.\d{3})+$/', $rateVal)) {
                        $rateVal = str_replace('.', '', $rateVal);
                    }
                } elseif (str_contains($rateVal, ',')) {
                    if ((substr_count($rateVal, ',') > 1) || preg_match('/^[1-9]\d{0,2}(,\d{3})+$/', $rateVal)) {
                        $rateVal = str_replace(',', '', $rateVal);
                    } else {
                        $rateVal = str_replace(',', '.', $rateVal);
                    }
                }
                if (preg_match('/^0+[1-9]/', $rateVal)) {
                    $rateVal = ltrim($rateVal, '0');
                }
                $this->merge(['exchange_rate' => $rateVal]);
            }
        }

        $rawPph = $this->input('pph23_amount');
        if ($rawPph === null || trim((string) $rawPph) === '') {
            $this->merge(['pph23_amount' => '0']);
        }

        foreach (['unit_cost', 'unit_price', 'pph23_amount', 'exchange_rate'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $val = trim($this->input($field));
                if ($val === '') {
                    continue;
                }
                $val = preg_replace('/^Rp\s*/i', '', $val);
                if (str_contains($val, '.') && str_contains($val, ',')) {
                    if (strrpos($val, ',') > strrpos($val, '.')) {
                        $val = str_replace('.', '', $val);
                        $val = str_replace(',', '.', $val);
                    } else {
                        $val = str_replace(',', '', $val);
                    }
                } elseif (str_contains($val, '.')) {
                    if ((substr_count($val, '.') > 1) || preg_match('/^[1-9]\d{0,2}(\.\d{3})+$/', $val)) {
                        $val = str_replace('.', '', $val);
                    }
                } elseif (str_contains($val, ',')) {
                    if ((substr_count($val, ',') > 1) || preg_match('/^[1-9]\d{0,2}(,\d{3})+$/', $val)) {
                        $val = str_replace(',', '', $val);
                    } else {
                        $val = str_replace(',', '.', $val);
                    }
                }
                if (preg_match('/^0+[1-9]/', $val)) {
                    $val = ltrim($val, '0');
                }
                $this->merge([$field => $val]);
            }
        }
    }

    public function rules(): array
    {
        $money = ['required', 'regex:/^\d{1,9}(\.\d{1,2})?$/'];

        return [
            'job_version' => ['required', 'integer', 'min:0'],
            'lock_version' => [$this->route('cost') ? 'required' : 'nullable', 'integer', 'min:0'],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(CostType::class)],
            'cost_category' => ['required', 'string', 'in:reimbursement,debit_note,payment_request,credit_note'],
            'currency' => ['required', 'string', Rule::in(array_keys(config('operations.currencies')))],
            'exchange_rate' => ['required', 'numeric', 'min:0.0001', 'max:999999999.9999'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'cost_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$this->route('job')->job_date->format('Y-m-d')],
            'quantity' => ['required', 'regex:/^\\d{1,6}(\\.\\d{1,2})?$/', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:30'], 'unit_cost' => $money, 'unit_price' => $money,
            'pph23_amount' => ['nullable', 'numeric', 'min:0'],
            'payee' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['description' => 'uraian biaya', 'type' => 'jenis biaya', 'cost_category' => 'kategori biaya', 'vendor_id' => 'vendor', 'cost_date' => 'tanggal biaya', 'quantity' => 'jumlah', 'unit' => 'satuan', 'unit_cost' => 'modal per unit', 'unit_price' => 'nilai jual per unit', 'payee' => 'penerima/vendor', 'reference' => 'nomor bukti', 'notes' => 'catatan', 'job_version' => 'versi job', 'lock_version' => 'versi biaya'];
    }
}
