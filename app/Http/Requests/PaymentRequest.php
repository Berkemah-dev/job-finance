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
