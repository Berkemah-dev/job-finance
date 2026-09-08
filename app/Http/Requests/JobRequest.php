<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('job'));
    }

    public function rules(): array
    {
        return ['lock_version' => ['required', 'integer', 'min:0'], 'subject' => ['required', 'string', 'max:255'],
            'job_date' => ['required', 'date_format:Y-m-d'], 'expected_completion_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:job_date'],
            'service_type' => ['nullable', Rule::in(array_keys(config('operations.service_types')))],
            'origin' => ['nullable', 'string', 'max:255'], 'destination' => ['nullable', 'string', 'max:255'],
            'shipment_reference' => ['nullable', 'string', 'max:100'], 'cargo_description' => ['nullable', 'string', 'max:2000'], 'operational_notes' => ['nullable', 'string', 'max:5000']];
    }

    public function attributes(): array
    {
        return ['lock_version' => 'versi data', 'subject' => 'nama pekerjaan', 'job_date' => 'tanggal job', 'expected_completion_date' => 'target selesai', 'service_type' => 'jenis layanan', 'origin' => 'asal', 'destination' => 'tujuan', 'shipment_reference' => 'referensi pengiriman', 'cargo_description' => 'deskripsi muatan', 'operational_notes' => 'catatan operasional'];
    }
}
