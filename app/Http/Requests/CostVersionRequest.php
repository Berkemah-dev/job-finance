<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CostVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('costs.manage');
    }

    public function rules(): array
    {
        return ['job_version' => ['required', 'integer', 'min:0'], 'lock_version' => ['required', 'integer', 'min:0']];
    }
}
