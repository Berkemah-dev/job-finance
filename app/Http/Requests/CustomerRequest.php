<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('customers.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'regex:/^[A-Z0-9-]+$/', 'max:30', Rule::unique('customers', 'code')->ignore($this->route('customer'))],
            'name' => ['required', 'string', 'max:255'], 'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:2000'], 'tax_number' => ['nullable', 'string', 'max:40'],
            'lock_version' => [$this->isMethod('PUT') ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'kode customer', 'name' => 'nama customer', 'contact_name' => 'nama kontak', 'address' => 'alamat', 'tax_number' => 'NPWP', 'lock_version' => 'versi data'];
    }
}
