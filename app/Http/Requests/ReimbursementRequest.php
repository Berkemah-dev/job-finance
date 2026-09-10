<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'category' => ['required', 'in:'.implode(',', array_keys(config('jobfinance.reimbursement_categories')))],
            'reimbursement_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'min:3', 'max:255'],
            'amount' => ['required', 'regex:/^\d{1,15}(\.\d{1,2})?$/', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => 'karyawan',
            'category' => 'kategori',
            'reimbursement_date' => 'tanggal',
            'description' => 'keterangan',
            'amount' => 'jumlah',
            'notes' => 'catatan',
        ];
    }
}
