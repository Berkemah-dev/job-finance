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

    public function rules(): array
    {
        $paymentTerms = array_merge(array_keys(config('operations.customer_payment_terms')), ['']);

        return [
            // Kode customer dibuat oleh backend; input manual tidak diterima.
            'code' => ['sometimes', 'prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s()]+$/'],
            'address' => ['nullable', 'string', 'max:2000'],
            'authorizer_name' => ['nullable', 'string', 'max:255'],
            'authorizer_title' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:40', 'regex:/^[0-9]{15,16}$/'],
            'default_payment_terms' => ['nullable', 'string', 'max:60', Rule::in($paymentTerms)],
            'npwp_file' => ['nullable', 'file', 'mimes:'.implode(',', config('operations.customer_documents.mimes')), 'max:'.config('operations.customer_documents.max_kb')],
            'nib_file' => ['nullable', 'file', 'mimes:'.implode(',', config('operations.customer_documents.mimes')), 'max:'.config('operations.customer_documents.max_kb')],
            'contacts' => ['nullable', 'array', 'max:20'],
            'contacts.*.type' => ['required', 'in:shipper,consignee'],
            'contacts.*.name' => ['required', 'string', 'max:255'],
            'contacts.*.company' => ['nullable', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:40'],
            'contacts.*.address' => ['nullable', 'string', 'max:2000'],
            'contacts.*.country' => ['nullable', 'string', 'max:120'],
            'contacts.*.notes' => ['nullable', 'string', 'max:2000'],
            'contacts.*.is_active' => ['nullable', 'boolean'],
            'lock_version' => [$this->isMethod('PUT') ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama customer',
            'contact_name' => 'nama kontak',
            'address' => 'alamat',
            'authorizer_name' => 'nama pemberi kuasa',
            'authorizer_title' => 'jabatan pemberi kuasa',
            'tax_number' => 'NPWP',
            'phone' => 'telepon',
            'default_payment_terms' => 'syarat pembayaran',
            'lock_version' => 'versi data',
        ];
    }
}
