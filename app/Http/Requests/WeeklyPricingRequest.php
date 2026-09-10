<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WeeklyPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pricing.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        return [
            'week' => ['required', 'string', 'max:20'],
            'effective_date' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'currency' => ['required', Rule::in(array_keys(config('operations.currencies')))],
            'exchange_rate' => ['required', 'numeric', 'min:0.000001', 'max:99999999999.99'],
            'service' => ['nullable', 'string', 'max:40', Rule::in(array_keys(config('operations.service_types')))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'lock_version' => [$this->isMethod('PUT') ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return ['week' => 'pekan', 'effective_date' => 'tanggal berlaku', 'effective_until' => 'berlaku sampai', 'currency' => 'mata uang', 'exchange_rate' => 'kurs', 'is_active' => 'status aktif', 'lock_version' => 'versi data'];
    }
}
