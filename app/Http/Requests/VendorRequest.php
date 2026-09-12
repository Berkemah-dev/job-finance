<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('vendors.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $types = array_keys(config('operations.vendor_types'));

        return [
            'code' => ['required', 'regex:/^[A-Z0-9-]+$/', 'max:30', Rule::unique('vendors', 'code')->ignore($this->route('vendor'))],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in($types)],
            'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:2000'],
            'country' => ['nullable', 'string', 'max:120'],
            'tax_number' => ['nullable', 'string', 'max:40'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'pic' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'lock_version' => [$this->isMethod('PUT') ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'kode vendor',
            'name' => 'nama vendor',
            'type' => 'kategori vendor',
            'tax_number' => 'NPWP / TAX ID',
            'bank_name' => 'nama bank',
            'bank_account_number' => 'nomor rekening',
            'bank_account_name' => 'nama pemilik rekening',
            'pic' => 'PIC',
            'is_active' => 'status aktif',
            'lock_version' => 'versi data',
        ];
    }
}
