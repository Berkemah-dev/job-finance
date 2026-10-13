<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reports.view');
    }

    public function rules(): array
    {
        return ['from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:today'], 'account_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'], 'payment_status' => ['nullable', 'in:paid,unpaid'], 'customer' => ['nullable', 'string', 'max:100'], 'vendor' => ['nullable', 'string', 'max:100'], 'agent' => ['nullable', 'string', 'max:100'], 'job_number' => ['nullable', 'string', 'max:100'], 'invoice_number' => ['nullable', 'string', 'max:100'], 'cost_name' => ['nullable', 'string', 'max:100'], 'pr_number' => ['nullable', 'string', 'max:100']];
    }
}
