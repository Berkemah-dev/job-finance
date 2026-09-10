<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('jobs.close');
    }

    public function rules(): array
    {
        return ['lock_version' => ['required', 'integer', 'min:0'], 'closing_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$this->route('job')->job_date->format('Y-m-d'), 'before_or_equal:today'], 'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:closing_date'], 'funding_account' => ['required', Rule::in(['cash', 'bank'])], 'tax' => ['required', 'regex:/^\\d{1,9}(\\.\\d{1,2})?$/'], 'exchange_rate_override' => ['nullable', 'regex:/^\\d{1,9}(\\.\\d{1,2})?$/', 'gt:0']];
    }
}
