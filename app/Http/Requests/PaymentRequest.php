<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('payments.manage');
    }

    protected function prepareForValidation(): void
    {
        foreach (['amount', 'pph23_amount'] as $field) {
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
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'payment_date' => ['required', 'date', 'after_or_equal:'.$this->route('invoice')->invoice_date->format('Y-m-d'), 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01', 'regex:/^\d{1,16}(\.\d{1,2})?$/'],
            'pph23_amount' => ['nullable', 'numeric', 'min:0', 'regex:/^\d{1,16}(\.\d{1,2})?$/'],
            'currency' => ['nullable', 'string', 'max:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
            'deposit_account' => ['required', 'string'],
            'method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
