<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('coa.manage');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'regex:/^[0-9A-Za-z.-]+$/', 'max:20', Rule::unique('chart_of_accounts', 'code')->ignore($this->route('account'))],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(config('accounting.types')))],
            'parent_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'lock_version' => [$this->isMethod('PUT') ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'kode akun', 'name' => 'nama akun', 'type' => 'tipe akun', 'parent_id' => 'akun induk', 'lock_version' => 'versi data'];
    }
}
