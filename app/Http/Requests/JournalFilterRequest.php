<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JournalFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('journals.manage');
    }

    public function rules(): array
    {
        return ['type' => ['nullable', Rule::in(['job_cost_capitalization', 'job_closing', 'customer_payment', 'adjustment', 'journal_reversal'])], 'from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:today']];
    }
}
