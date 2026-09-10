<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReimbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reimbursements.manage');
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:users,id'],
            'job_id' => ['nullable', 'integer', 'exists:jobs,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'category' => ['required', 'in:'.implode(',', array_keys(config('jobfinance.reimbursement_categories')))],
            'reimbursement_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'min:3', 'max:255'],
            'amount' => ['required', 'regex:/^\d{1,15}(\.\d{1,2})?$/', 'gt:0'],
            'currency' => ['nullable', 'string', Rule::in(array_keys(config('operations.currencies')))],
            'exchange_rate' => ['nullable', 'regex:/^\d{1,9}(\.\d{1,2})?$/', 'gt:0'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => 'karyawan',
            'job_id' => 'job',
            'vendor_id' => 'vendor',
            'category' => 'kategori',
            'reimbursement_date' => 'tanggal',
            'description' => 'keterangan',
            'amount' => 'jumlah',
            'currency' => 'mata uang',
            'exchange_rate' => 'kurs',
            'attachment' => 'lampiran',
            'notes' => 'catatan',
        ];
    }
}
