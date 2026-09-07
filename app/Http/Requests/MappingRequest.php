<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('coa.manage');
    }

    public function rules(): array
    {
        $rules = ['mappings' => ['required', 'array:'.implode(',', array_keys(config('accounting.mappings')))]];
        foreach (config('accounting.mappings') as $key => $settings) {
            $rules['mappings.'.$key] = ['required', 'integer'];
        }

        return $rules;
    }
}
