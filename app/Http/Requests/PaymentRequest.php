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
        return ['lock_version' => ['required', 'integer', 'min:0'], 'payment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$this->route('invoice')->invoice_date->format('Y-m-d'), 'before_or_equal:today'], 'amount' => ['required', 'regex:/^\\d{1,16}(\\.\\d{1,2})?$/', 'numeric', 'min:0.01'], 'deposit_account' => ['required', Rule::in(['cash', 'bank'])], 'method' => ['required', Rule::in(['transfer', 'cash', 'giro', 'other'])], 'reference' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:2000']];
    }
}
