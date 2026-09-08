<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JournalReversalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('journals.manage');
    }

    public function rules(): array
    {
        return ['lock_version' => ['required', 'integer', 'min:0'], 'reversal_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'reason' => ['required', 'string', 'max:1000']];
    }
}
