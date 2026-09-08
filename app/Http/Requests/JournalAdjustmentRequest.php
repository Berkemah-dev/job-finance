<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JournalAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('journals.manage');
    }

    public function rules(): array
    {
        return [
            'journal_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:255'],
            'entries' => ['required', 'array', 'min:2', 'max:50'],
            'entries.*.account_id' => ['required', 'integer', 'distinct', 'exists:chart_of_accounts,id'],
            'entries.*.description' => ['required', 'string', 'max:255'],
            'entries.*.debit' => ['nullable', 'regex:/^\d{1,16}(\.\d{1,2})?$/'],
            'entries.*.credit' => ['nullable', 'regex:/^\d{1,16}(\.\d{1,2})?$/'],
        ];
    }
}
