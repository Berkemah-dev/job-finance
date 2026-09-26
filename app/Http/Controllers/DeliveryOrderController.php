<?php

namespace App\Http\Controllers;

use App\Models\CustomerAddress;
use App\Models\DeliveryOrder;
use App\Models\Job;
use App\Services\MasterDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DeliveryOrderController extends Controller
{
    public function store(Request $request, Job $job)
    {
        Gate::authorize('update', $job);

        $data = $request->validate([
            'customer_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'use_manual_delivery_address' => ['nullable', 'boolean'],
            'delivery_address' => ['nullable', 'string', 'max:5000'],
            'container_number' => ['nullable', 'string', 'max:120'],
            'truck_plate_number' => ['nullable', 'string', 'max:30'],
            'driver_name' => ['nullable', 'string', 'max:160'],
            'driver_phone' => ['nullable', 'string', 'max:50'],
            'vehicle_type' => ['nullable', 'string', 'max:60'],
        ]);

        $manual = $request->boolean('use_manual_delivery_address');
        $address = null;
        $addressLabel = null;
        $addressId = null;

        if (! $manual && ! empty($data['customer_address_id'])) {
            $addressRecord = CustomerAddress::where('customer_id', $job->customer_id)
                ->where('is_active', true)
                ->findOrFail($data['customer_address_id']);
            $address = $addressRecord->address;
            $addressLabel = $addressRecord->location_name;
            $addressId = $addressRecord->id;
        } elseif (! $manual) {
            $addressRecord = CustomerAddress::where('customer_id', $job->customer_id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
            $address = $addressRecord?->address ?: $job->consignee_address;
            $addressLabel = $addressRecord?->location_name;
            $addressId = $addressRecord?->id;
        } else {
            $address = trim((string) ($data['delivery_address'] ?? ''));
        }

        if ($address === '') {
            return back()->withErrors(['delivery_address' => 'Pilih alamat master customer atau isi alamat manual.'])->withInput();
        }

        $deliveryOrder = DeliveryOrder::create([
            'job_id' => $job->id,
            'number' => 'PENDING',
            'issued_at' => today(),
            'customer_address_id' => $addressId,
            'address_label' => $addressLabel,
            'delivery_address' => $address,
            'container_number' => ($data['container_number'] ?? null) ?: $job->container_number,
            'truck_plate_number' => ($data['truck_plate_number'] ?? null) ?: ($job->truck_plate_number ?: $job->vendorTruck?->plate_number),
            'driver_name' => ($data['driver_name'] ?? null) ?: ($job->driver_name ?: $job->vendorTruck?->driver_name),
            'driver_phone' => ($data['driver_phone'] ?? null) ?: ($job->driver_phone ?: $job->vendorTruck?->driver_phone),
            'vehicle_type' => ($data['vehicle_type'] ?? null) ?: ($job->vehicle_type ?: $job->vendorTruck?->vehicle_type),
            'created_by' => $request->user()->id,
        ]);
        $deliveryOrder->update(['number' => 'DO-RDX/'.today()->format('Y').'/'.str_pad((string) $deliveryOrder->id, 4, '0', STR_PAD_LEFT)]);

        return redirect()->to(route('jobs.show', $job).'#tab-delivery')
            ->with('success', 'Surat Jalan '.$deliveryOrder->number.' berhasil dibuat.');
    }

    public function pdf(Request $request, DeliveryOrder $deliveryOrder, MasterDataService $master)
    {
        $job = $deliveryOrder->job()->with(['customer', 'quotation', 'vendorTrucking', 'vendorTruck'])->firstOrFail();
        Gate::authorize('view', $job);

        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.surat-jalan', [
            'job' => $job,
            'quotation' => $job->quotation,
            'deliveryOrder' => $deliveryOrder,
        ])->setPaper('a4');
        $master->log($request->user(), 'document.generated', 'Mencetak Surat Jalan '.$deliveryOrder->number, ['module' => 'delivery_order', 'record_id' => $deliveryOrder->id]);

        $filename = str_replace('/', '_', $deliveryOrder->number).'.pdf';
        return $request->query('mode') === 'download'
            ? $pdf->download($filename)
            : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$filename.'"']);
    }
}
