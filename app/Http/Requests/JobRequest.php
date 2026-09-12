<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ServiceType;
use App\Models\ContainerUnit;

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
            'service_type' => ['nullable', Rule::in(array_keys(ServiceType::options()))],
            'origin' => ['nullable', 'string', 'max:255'], 'destination' => ['nullable', 'string', 'max:255'],
            'shipment_reference' => ['nullable', 'string', 'max:100'],
            'shipper_name' => ['nullable', 'string', 'max:160'], 'shipper_address' => ['nullable', 'string', 'max:5000'],
            'consignee_name' => ['nullable', 'string', 'max:160'], 'consignee_address' => ['nullable', 'string', 'max:5000'],
            'pol' => ['nullable', 'string', 'max:120'], 'pod' => ['nullable', 'string', 'max:120'],
            'etd' => ['nullable', 'date_format:Y-m-d'], 'eta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:etd'],
            'vessel_voyage' => ['nullable', 'string', 'max:120'], 'flight_number' => ['nullable', 'string', 'max:60'],
            'bl_number' => ['nullable', 'string', 'max:60'], 'hbl_number' => ['nullable', 'string', 'max:60'],
            'awb_number' => ['nullable', 'string', 'max:60'], 'hawb_number' => ['nullable', 'string', 'max:60'],
            'booking_reference' => ['nullable', 'string', 'max:60'],
            'nopen' => ['nullable', 'string', 'max:60'], 'npe_number' => ['nullable', 'string', 'max:60'],
            'package_count' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'gross_weight' => ['nullable', 'regex:/^\d{1,9}(\.\d{1,2})?$/'], 'volume' => ['nullable', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'container_type' => ['nullable', Rule::in(array_keys(ContainerUnit::options()))],
            'sales_id' => ['nullable', 'integer', 'exists:users,id'], 'cs_id' => ['nullable', 'integer', 'exists:users,id'],
            'cargo_description' => ['nullable', 'string', 'max:2000'], 'operational_notes' => ['nullable', 'string', 'max:5000']];
    }

    public function attributes(): array
    {
        return ['lock_version' => 'versi data', 'subject' => 'nama pekerjaan', 'job_date' => 'tanggal job', 'expected_completion_date' => 'target selesai', 'service_type' => 'jenis layanan', 'origin' => 'asal', 'destination' => 'tujuan', 'shipment_reference' => 'referensi pengiriman', 'shipper_name' => 'pengirim', 'shipper_address' => 'alamat pengirim', 'consignee_name' => 'penerima', 'consignee_address' => 'alamat penerima', 'pol' => 'pelabuhan muat/POL', 'pod' => 'pelabuhan bongkar/POD', 'etd' => 'perkiraan berangkat/ETD', 'eta' => 'perkiraan tiba/ETA', 'vessel_voyage' => 'vessel & voyage', 'flight_number' => 'nomor penerbangan', 'bl_number' => 'nomor BL', 'hbl_number' => 'nomor HBL', 'awb_number' => 'nomor AWB', 'hawb_number' => 'nomor HAWB', 'booking_reference' => 'nomor pengajuan / No AJU', 'nopen' => 'nomor pendaftaran / Nopen', 'npe_number' => 'nomor NPE', 'package_count' => 'jumlah paket', 'gross_weight' => 'berat kotor', 'volume' => 'volume muatan', 'container_type' => 'jenis kontainer', 'sales_id' => 'sales', 'cs_id' => 'customer service', 'cargo_description' => 'deskripsi muatan', 'operational_notes' => 'catatan operasional'];
    }
}
