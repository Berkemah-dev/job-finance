@extends('layouts.app')
@section('title','Detail Job Order ' . $job->number)
@section('content')

@php
    $quotation = $job->quotation;
    $customerName = $job->customer?->name ?? $job->quotation_snapshot['customer']['name'] ?? '—';
    $marketingName = $job->sales?->name ?? $quotation?->sales?->name ?? '—';
    $serviceTypeRaw = strtolower($job->service_type ?? '');
    
    // Categorize shipment service
    $isExportSea = $serviceTypeRaw === 'exp_sea' || ($serviceTypeRaw === 'sea' && !str_contains($serviceTypeRaw, 'imp'));
    $isExportAir = $serviceTypeRaw === 'exp_air' || ($serviceTypeRaw === 'air' && !str_contains($serviceTypeRaw, 'imp'));
    $isImport = str_contains($serviceTypeRaw, 'imp');
    $isAir = $isExportAir || $serviceTypeRaw === 'imp_air' || str_contains($serviceTypeRaw, 'air');
    $isDomestic = str_contains($serviceTypeRaw, 'dom') || $serviceTypeRaw === 'land' || $serviceTypeRaw === 'domestic';
    
    // Fallback default
    if (!$isExportSea && !$isExportAir && !$isImport && !$isDomestic) {
        if (str_contains($serviceTypeRaw, 'exp')) {
            $isExportSea = true;
        } else {
            $isImport = true;
        }
    }

    $serviceCategoryTitle = $isExportSea ? 'EXPORT SHIPMENT (SEA)' : 
        ($isExportAir ? 'EXPORT SHIPMENT (AIR)' : 
        ($serviceTypeRaw === 'imp_sea' ? 'IMPORT SHIPMENT (SEA)' :
        ($serviceTypeRaw === 'imp_air' ? 'IMPORT SHIPMENT (AIR)' : 'DOMESTIC / TRUCKING')));

    $serviceTypeLabel = \App\Models\ServiceType::label($job->service_type);
    $loadingPort = $job->pol ?? $job->origin ?? $quotation?->origin ?? '—';
    $dischargePort = $job->pod ?? $job->destination ?? $quotation?->destination ?? '—';
    $etdDate = $job->etd ? $job->etd->format('d-m-Y') : '—';
    $etaDate = $job->eta ? $job->eta->format('d-m-Y') : '—';
    $noAju = $job->booking_reference ?? '—';
    $noNopen = $job->nopen ?? '—';
    $nopenDate = $job->nopen_date ? $job->nopen_date->format('d/m/Y') : '—';
    $noNpe = $job->npe_number ?? '—';
    $noPeb = $job->peb_number ?? '—';
    $pebDate = $job->peb_date ? $job->peb_date->format('d/m/Y') : '—';
    $noHbl = $job->hbl_number ?? $job->hawb_number ?? '—';
    $noMbl = $job->bl_number ?? $job->awb_number ?? '—';
    $vesselName = $job->vessel_voyage ?? $job->flight_number ?? '—';
    $quantityStr = $job->package_count ? $job->package_count . ' Box' : ($job->container_type ? '1x ' . strtoupper($job->container_type) : ($quotation?->cargo_qty ?? '—'));
    $grossWeightStr = $job->gross_weight ? \App\Support\Money::format($job->gross_weight) . ' KGS' : ($quotation?->weight_meas ?? '—');
    $quotationVolume = $quotation?->items?->sum(fn($i) => (float)($i->volume ?? 0));
    $volumeStr = $job->volume ? $job->volume . ' M3' : ($quotationVolume > 0 ? $quotationVolume . ' M3' : '—');
    $commodityStr = $job->cargo_description ?? $quotation?->commodity ?? 'General Cargo';
    $noteContent = $job->operational_notes ?? $quotation?->notes ?? '';
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">OPERASIONAL / JOB ORDER · {{ $serviceCategoryTitle }}</p>
        <h1>{{ $job->number }}</h1>
        <p>{{ $job->subject }} · Customer: <strong>{{ $customerName }}</strong>@if($job->pol || $job->pod) · {{ $job->pol ?? '—' }} → {{ $job->pod ?? '—' }}@elseif($job->origin || $job->destination) · {{ $job->origin ?? '—' }} → {{ $job->destination ?? '—' }}@endif</p>
    </div>
    <a class="button button-secondary" href="{{ route('jobs.index') }}">← Kembali</a>
</div>

    @if($job->statusHistory->isNotEmpty())
        <section class="panel" style="margin-bottom: 24px;">
            <div class="panel-heading"><h2>Riwayat status</h2></div>
            <ol class="approval-timeline">
                @foreach($job->statusHistory as $event)
                    <li>
                        <span></span>
                        <div>
                            <strong>
                                @if($event->from_status)
                                    {{ config('operations.job_statuses.'.$event->from_status) ?? $event->from_status }} → {{ config('operations.job_statuses.'.$event->to_status) ?? $event->to_status }}
                                @else
                                    {{ config('operations.job_statuses.'.$event->to_status) ?? $event->to_status }}
                                @endif
                            </strong>
                            <p>{{ $event->user?->name ?? 'System' }} · {{ $event->created_at->format('d/m/Y H:i') }}@if($event->note)<br>{{ $event->note }}@endif</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

{{-- TOP ACTION BUTTONS --}}
<div class="quote-actions" style="margin-bottom: 20px;">
    @can('update',$job)
        <a class="button button-secondary" href="{{ route('jobs.edit',$job) }}">Edit Operasional</a>
    @endcan
    <a class="button button-secondary" href="{{ route('jobs.preview', $job) }}" target="_blank">🖨 Preview PDF Job</a>
    
    @can('open',$job)
        <form method="POST" action="{{ route('jobs.open',$job) }}" data-confirm="Buka job ini? Finance dapat mulai mencatat biaya setelah job berstatus Open.">
            @csrf
            <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">
            <button class="button button-primary">Buka Job</button>
        </form>
    @endcan
    @can('costs.manage')
        <a class="button button-primary" href="{{ route('jobs.costs.index',$job) }}"><x-icon name="wallet"/>Lihat Biaya Aktual</a>
    @endcan
    @can('jobs.close')
        @if($job->status==='open')
            <a class="button button-primary" href="{{ route('closing.create',$job) }}">Closing Job</a>
        @elseif($job->status==='closed' && (!$job->invoice || ($job->invoice->payments()->doesntExist() && (float)$job->invoice->paid_amount == 0)))
            <button type="button" class="button button-secondary" style="color:#dc2626; border-color:#fca5a5;" onclick="document.getElementById('modal-reopen-job').showModal()">
                ↺ Undo / Buka Kembali Job
            </button>
        @endif
    @endcan
    @can('invoices.manage')
        @if($job->invoice)
            <a class="button button-primary" href="{{ route('invoices.show',$job->invoice) }}">Lihat Invoice</a>
        @endif
    @endcan
    @if($job->status==='open' && !$job->do_confirmed_at)
        @can('jobs.confirm-do')
            <button type="button" class="button button-primary" style="background:#16a34a;border-color:#16a34a" onclick="document.getElementById('modal-confirm-do').showModal()">
                <x-icon name="check"/> DO Selesai
            </button>
        @endcan
    @elseif($job->do_confirmed_at)
        <span class="status-badge status-paid">DO Selesai ({{ $job->do_confirmed_at->format('d/m/Y H:i') }})@if($job->doConfirmedBy)<br><small>oleh {{ $job->doConfirmedBy->name }}</small>@endif</span>
    @endif
    @can('cancel', $job)
        <button type="button" class="button button-danger" onclick="document.getElementById('modal-cancel-job').showModal()">
            <x-icon name="x"/> Batalkan Job
        </button>
    @endcan
</div>

@can('cancel', $job)
<dialog id="modal-cancel-job" class="modal-dialog" style="max-width: 520px !important;">
    <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border-top-left-radius: 18px; border-top-right-radius: 18px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #fee2e2; color: #e11d48; font-size: 20px; box-shadow: 0 2px 6px rgba(225,29,72,0.15);">
                ⚠️
            </div>
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #9f1239;">Pembatalan Job Order</h3>
                <p style="margin: 2px 0 0; font-size: 12px; color: #be123c;">Batalkan pekerjaan ini jika terjadi revisi penawaran.</p>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('modal-cancel-job').close()" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #fecdd3; background: #fff; color: #be123c; display: grid; place-items: center; cursor: pointer; font-size: 14px; transition: all .15s ease;">✕</button>
    </div>

    <form method="POST" action="{{ route('jobs.cancel', $job) }}" style="padding: 20px 24px;">
        @csrf
        <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">
        <p style="color: #475569; font-size: 13.5px; line-height: 1.5; margin-top: 0;">
            Apakah Anda yakin ingin membatalkan Job Order <strong>{{ $job->number }}</strong>?
            Setelah dibatalkan, Sales Manager dapat mengedit kembali Quotation terkait.
        </p>
        <div style="margin: 16px 0;">
            <label for="cancel_reason" style="display: block; font-weight: 700; font-size: 12.5px; margin-bottom: 6px; color: #334155;">Alasan Pembatalan <span style="color: #e11d48;">*</span></label>
            <textarea id="cancel_reason" name="reason" rows="3" required placeholder="Jelaskan alasan pembatalan Job Order..." style="width: 100%; padding: 10px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 13.5px; resize: vertical; box-sizing: border-box;"></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
            <button type="button" class="button button-secondary" onclick="document.getElementById('modal-cancel-job').close()">Batal</button>
            <button type="submit" class="button button-danger">Ya, Batalkan Job</button>
        </div>
    </form>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cancelModal = document.getElementById('modal-cancel-job');
    if (cancelModal) {
        cancelModal.addEventListener('click', function(e) {
            const rect = cancelModal.getBoundingClientRect();
            const inDialog = (rect.top <= e.clientY && e.clientY <= rect.top + rect.height && rect.left <= e.clientX && e.clientX <= rect.left + rect.width);
            if (!inDialog) cancelModal.close();
        });
    }
});
</script>
@endcan

@can('jobs.close')
    @if($job->status === 'closed' && (!$job->invoice || ($job->invoice->payments()->doesntExist() && (float)$job->invoice->paid_amount == 0)))
    <dialog id="modal-reopen-job" class="modal-dialog" style="max-width: 500px !important; border: none; border-radius: 16px; padding: 0; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <form method="POST" action="{{ route('jobs.reopen', $job) }}">
            @csrf
            <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">
            <div style="padding: 18px 24px; border-bottom: 1px solid #fee2e2; display: flex; justify-content: space-between; align-items: center; background: #fef2f2; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">↺</span>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #991b1b;">Undo / Buka Kembali Job</h3>
                </div>
                <button type="button" onclick="document.getElementById('modal-reopen-job').close()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #991b1b;">✕</button>
            </div>
            <div style="padding: 20px 24px;">
                <p style="font-size: 13px; color: #374151; margin-top: 0; line-height: 1.5;">
                    Apakah Anda yakin ingin membuka kembali job <strong>{{ $job->number }}</strong>?
                </p>
                <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 12px; font-size: 12px; color: #92400e; margin-bottom: 16px; line-height: 1.5;">
                    ⚠️ Tindakan ini akan mengembalikan status Job ke <strong>Open</strong>, membatalkan/menghapus invoice {{ $job->invoice?->number }}, dan me-reverse jurnal penutupan akuntansi secara otomatis.
                </div>
                <div class="field">
                    <label for="reopen_reason_job" style="font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; display: block;">Alasan Buka Kembali (opsional)</label>
                    <input type="text" id="reopen_reason_job" name="reason" placeholder="cth: Salah input biaya aktual / revisi operasional" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 13px; box-sizing: border-box;">
                </div>
            </div>
            <div style="padding: 14px 24px; border-top: 1px solid #e5e7eb; background: #f9fafb; display: flex; justify-content: flex-end; gap: 10px; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('modal-reopen-job').close()">Batal</button>
                <button type="submit" class="button button-danger" style="background: #dc2626; border-color: #dc2626; color: #ffffff !important; font-weight: 600;">Ya, Buka Kembali Job</button>
            </div>
        </form>
    </dialog>
    @endif
@endcan

@can('jobs.confirm-do')
    @if($job->status === 'open' && !$job->do_confirmed_at)
    @php
        $hasSj = $job->hasSuratJalanDocument();
    @endphp
    <dialog id="modal-confirm-do" class="modal-dialog" style="max-width: 520px !important; border: none; border-radius: 16px; padding: 0; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <form method="POST" action="{{ route('jobs.confirm-do', $job) }}" enctype="multipart/form-data">
            @csrf
            <div style="padding: 18px 24px; border-bottom: 1px solid #dcfce7; display: flex; justify-content: space-between; align-items: center; background: #f0fdf4; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">📦</span>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #166534;">Konfirmasi DO Selesai</h3>
                </div>
                <button type="button" onclick="document.getElementById('modal-confirm-do').close()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #166534;">✕</button>
            </div>
            <div style="padding: 20px 24px;">
                <p style="font-size: 13.5px; color: #374151; margin-top: 0; line-height: 1.5;">
                    Konfirmasi bahwa pengiriman <strong>Delivery Order (DO)</strong> untuk job <strong>{{ $job->number }}</strong> telah selesai dan barang telah diterima oleh customer.
                </p>

                @if(!$hasSj)
                    <div style="background: #fef2f2; border: 1.5px solid #fca5a5; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px;">
                        <label for="modal_surat_jalan_file" style="font-size: 12.5px; font-weight: 700; color: #991b1b; display: block; margin-bottom: 4px;">
                            Upload Berkas Surat Jalan (Wajib) <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="file" name="surat_jalan_file" id="modal_surat_jalan_file" accept=".pdf,.jpg,.jpeg,.png" required style="font-size: 12px; padding: 6px 10px; border: 1px solid #fca5a5; border-radius: 6px; background: #fff; width: 100%; box-sizing: border-box;">
                        <small style="color: #64748b; font-size: 11.5px; display: block; margin-top: 4px;">Wajib melampirkan foto/scan Surat Jalan yang telah ditandatangani sebelum konfirmasi selesai.</small>
                    </div>
                @else
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 12.5px; color: #166534; display: flex; align-items: center; gap: 8px;">
                        <span>✓</span>
                        <span>Berkas Surat Jalan fisik sudah diunggah di sistem.</span>
                    </div>
                @endif
            </div>
            <div style="padding: 14px 24px; border-top: 1px solid #e5e7eb; background: #f9fafb; display: flex; justify-content: flex-end; gap: 10px; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('modal-confirm-do').close()">Batal</button>
                <button type="submit" class="button button-primary" style="background: #16a34a; border-color: #16a34a;">
                    <x-icon name="check"/> Ya, Konfirmasi DO Selesai
                </button>
            </div>
        </form>
    </dialog>
    @endif
@endcan

{{-- HORIZONTAL PILL TABS MENU KE KANAN (SESUAI REQUEST & SCREENSHOT CLIENT) --}}
<nav class="job-pill-tabs-nav" style="display: flex; gap: 8px; background: #e2e8f0; padding: 6px; border-radius: 9999px; margin-bottom: 24px; overflow-x: auto;">
    <button type="button" class="job-tab-btn active" data-tab="tab-shipping" style="padding: 10px 22px; border-radius: 9999px; font-weight: 700; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: #fff; color: #0f172a; box-shadow: 0 1px 4px rgba(0,0,0,0.12);">1. Job Order</button>
    @if($isImport)
        <button type="button" class="job-tab-btn" data-tab="tab-customs" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">2. PIB</button>
        <button type="button" class="job-tab-btn" data-tab="tab-documents" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">3. Document Upload</button>
        <button type="button" class="job-tab-btn" data-tab="tab-sk" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">4. Surat Kuasa</button>
        <button type="button" class="job-tab-btn" data-tab="tab-dnp" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">5. Deklarasi Nilai Pabean</button>
        <button type="button" class="job-tab-btn" data-tab="tab-delivery" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">6. Surat Jalan / Tanda Terima</button>
        <button type="button" class="job-tab-btn" data-tab="tab-financial" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">7. @can('financial.view') Biaya & Profit @else Rincian Tagihan @endcan</button>
    @elseif($isExportSea)
        <button type="button" class="job-tab-btn" data-tab="tab-customs" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">2. PEB</button>
        <button type="button" class="job-tab-btn" data-tab="tab-documents" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">3. Document Upload</button>
        <button type="button" class="job-tab-btn" data-tab="tab-si" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">4. Shipping Instruction</button>
        <button type="button" class="job-tab-btn" data-tab="tab-booking" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">5. Booking Confirmation</button>
        <button type="button" class="job-tab-btn" data-tab="tab-bl" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">6. Bill of Lading (B/L)</button>
        <button type="button" class="job-tab-btn" data-tab="tab-delivery" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">7. Tanda Terima Dokumen</button>
        <button type="button" class="job-tab-btn" data-tab="tab-financial" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">8. @can('financial.view') Biaya & Profit @else Rincian Tagihan @endcan</button>
    @elseif($isExportAir)
        <button type="button" class="job-tab-btn" data-tab="tab-customs" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">2. PEB</button>
        <button type="button" class="job-tab-btn" data-tab="tab-documents" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">3. Document Upload</button>
        <button type="button" class="job-tab-btn" data-tab="tab-si" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">4. Shipping Instruction</button>
        <button type="button" class="job-tab-btn" data-tab="tab-booking" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">5. Booking Confirmation</button>
        <button type="button" class="job-tab-btn" data-tab="tab-awb" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">6. Air Waybill (AWB)</button>
        <button type="button" class="job-tab-btn" data-tab="tab-delivery" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">7. Tanda Terima Dokumen</button>
        <button type="button" class="job-tab-btn" data-tab="tab-financial" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">8. @can('financial.view') Biaya & Profit @else Rincian Tagihan @endcan</button>
    @else
        <button type="button" class="job-tab-btn" data-tab="tab-documents" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">2. Document Upload</button>
        <button type="button" class="job-tab-btn" data-tab="tab-delivery" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">3. Surat Jalan / Tanda Terima</button>
        <button type="button" class="job-tab-btn" data-tab="tab-financial" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">4. @can('financial.view') Biaya & Profit @else Rincian Tagihan @endcan</button>
    @endif
</nav>

{{-- ========================================================================= --}}
{{-- STATUS SHIPMENT & CHECKLIST DOKUMEN WIDGET                                --}}
{{-- ========================================================================= --}}
@php
    $checklist = $job->shipment_checklist_summary;
@endphp
@if(!empty($checklist['items']))
    <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; background: #e0f2fe; color: #0284c7; font-size: 14px; font-weight: 700;">✓</span>
                <strong style="font-size: 14.5px; color: #0f172a;">Status Shipment & Checklist Dokumen</strong>
                <span style="font-size: 12.5px; color: #64748b;">({{ $checklist['title'] }})</span>
            </div>
            <div>
                @if($checklist['is_all_completed'])
                    <span class="status-badge" style="background: #dcfce7; color: #166534; font-weight: 700; border: 1px solid #86efac;">
                        ✓ {{ $checklist['status_summary'] }}
                    </span>
                @else
                    <span class="status-badge" style="background: #fee2e2; color: #b91c1c; font-weight: 600; border: 1px solid #fca5a5;">
                        {{ $checklist['status_summary'] }}
                    </span>
                @endif
            </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            @foreach($checklist['items'] as $item)
                @php
                    $isCompleted = (bool) ($item['completed'] ?? false);
                    $boxBorder = $isCompleted ? '#bbf7d0' : '#fca5a5';
                    $boxBg = $isCompleted ? '#f0fdf4' : '#fef2f2';
                    $badgeBorder = $isCompleted ? '#86efac' : '#fca5a5';
                    $badgeBg = $isCompleted ? ($item['badge_bg'] ?? '#dcfce7') : '#fee2e2';
                    $badgeColor = $isCompleted ? ($item['badge_color'] ?? '#166534') : '#b91c1c';
                @endphp
                <div style="padding: 10px 14px; border-radius: 8px; border: 1.5px solid {{ $boxBorder }}; background: {{ $boxBg }}; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                    <div>
                        <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                            @if($isCompleted)
                                <span style="color: #16a34a; font-size: 12px;">●</span>
                            @else
                                <span style="color: #dc2626; font-size: 12px;">●</span>
                            @endif
                            {{ $item['label'] }}
                        </div>
                        <div style="font-size: 11.5px; color: {{ $isCompleted ? '#64748b' : '#991b1b' }}; margin-top: 2px;">{{ $item['sublabel'] }}</div>
                    </div>
                    <span style="font-size: 11.5px; font-weight: 700; padding: 3px 8px; border-radius: 6px; background: {{ $badgeBg }}; color: {{ $badgeColor }}; border: 1px solid {{ $badgeBorder }}; white-space: nowrap;">
                        {{ $item['badge_text'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ========================================================================= --}}
{{-- TAB 1: DATA PENGAPALAN                                                    --}}
{{-- ========================================================================= --}}
<div id="tab-shipping" class="job-tab-content">
    <section class="panel" style="overflow: hidden; margin-bottom: 24px; border: 1px solid #000; background: #fff;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 24px; border-bottom: 1px solid #000; background: #fff;">
            <div style="flex: 1;">
                <img src="{{ asset('images/logo.png') }}" alt="RDX Logistics" style="max-height: 55px; max-width: 220px;" onerror="this.style.display='none'">
                <div style="margin-top: 8px;">
                    <span class="status-badge status-{{ $job->status }}">{{ config('operations.job_statuses.'.$job->status) }}</span>
                    @if($job->shipment_status)
                        <span class="status-badge status-{{ $job->shipment_status }}" style="margin-left: 6px;">{{ config('operations.shipment_statuses.'.$job->shipment_status) ?? $job->shipment_status }}</span>
                    @endif
                    @if($job->status==='open' && $job->etaApproaching())
                        <span class="status-badge" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca; margin-left: 6px;">Mendekati ETA</span>
                    @endif
                </div>
            </div>
            <div style="width: 280px;">
                <div style="border: 1px solid #000; width: 100%; border-collapse: collapse; background: #fff;">
                    <div style="text-align: center; font-size: 15px; font-weight: 700; padding: 6px; letter-spacing: 1px; border-bottom: 1px solid #000; font-family: monospace, sans-serif;">
                        JOB ORDER
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <tr>
                            <td style="padding: 4px 8px; border-bottom: 1px solid #000; border-right: 1px solid #000; width: 38%; font-weight: 600;">JO. No</td>
                            <td style="padding: 4px 8px; border-bottom: 1px solid #000; font-weight: 700; color: #0f172a;">{{ $job->number }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 8px; border-bottom: 1px solid #000; border-right: 1px solid #000; font-weight: 600;">Date</td>
                            <td style="padding: 4px 8px; border-bottom: 1px solid #000; font-weight: 700;">{{ $job->job_date?->format('d-m-Y') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 8px; border-bottom: 1px solid #000; border-right: 1px solid #000; font-weight: 600;">Type</td>
                            <td style="padding: 4px 8px; border-bottom: 1px solid #000; font-weight: 700;">{{ $serviceTypeLabel }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 8px; border-right: 1px solid #000; font-weight: 600;">Marketing</td>
                            <td style="padding: 4px 8px; font-weight: 700;">{{ strtoupper($marketingName) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- DATA JOB ORDER TABLE --}}
        <div style="padding: 24px;">
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 13px;">
                <thead>
                    <tr>
                        <th colspan="2" style="background-color: #e2e8f0; color: #000; font-weight: 700; text-align: left; padding: 7px 12px; border: 1px solid #000; font-size: 13.5px; letter-spacing: 0.5px;">
                            DATA JOB ORDER ({{ $serviceCategoryTitle }})
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 25%; font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Customer</td>
                        <td style="width: 75%; font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $customerName }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Shipper</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">
                            {{ $job->shipper_name ?? '—' }}
                            @if($job->shipper_address)
                                <div style="font-size: 12px; font-weight: normal; color: #64748b; margin-top: 2px;">{{ $job->shipper_address }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Consignee</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">
                            {{ $job->consignee_name ?? '—' }}
                            @if($job->consignee_address)
                                <div style="font-size: 12px; font-weight: normal; color: #64748b; margin-top: 2px;">{{ $job->consignee_address }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Loading (POL)</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $loadingPort }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Discharge (POD)</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $dischargePort }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">ETD</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $etdDate }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">ETA</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $etaDate }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Vessel / Flight</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $vesselName }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">{{ $isAir ? 'MAWB / AWB' : 'MBL / BL' }}</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $noMbl }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">{{ $isAir ? 'HAWB' : 'HBL' }}</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $noHbl }}</td>
                    </tr>
                    @if($isImport)
                        <tr>
                            <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">No. AJU (6 digit)</td>
                            <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000; color: #c2410c;">{{ $noAju }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Nopen (PIB)</td>
                            <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">
                                {{ $noNopen }}
                                @if($job->nopen_date)
                                    <span style="font-weight: normal; color: #475569; margin-left: 6px;">(Tgl: {{ $nopenDate }})</span>
                                @endif
                            </td>
                        </tr>
                    @elseif($isExportSea || $isExportAir)
                        <tr>
                            <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">No. AJU (6 digit)</td>
                            <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000; color: #c2410c;">{{ $noAju }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">NOPEN PEB</td>
                            <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">
                                {{ $noPeb }}
                                @if($job->peb_date)
                                    <span style="font-weight: normal; color: #475569; margin-left: 6px;">(Tgl: {{ $pebDate }})</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">No. NPE</td>
                            <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $noNpe }}</td>
                        </tr>
                    @endif
                    @if($job->commercial_invoice_number)
                        <tr>
                            <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">No. Commercial Invoice</td>
                            <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">
                                {{ $job->commercial_invoice_number }}
                                @if($job->commercial_invoice_date)
                                    <span style="font-weight: normal; color: #475569; margin-left: 6px;">(Tgl: {{ $job->commercial_invoice_date->format('d/m/Y') }})</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                    @if($job->packing_list_number)
                        <tr>
                            <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">No. Packing List</td>
                            <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">
                                {{ $job->packing_list_number }}
                                @if($job->packing_list_date)
                                    <span style="font-weight: normal; color: #475569; margin-left: 6px;">(Tgl: {{ $job->packing_list_date->format('d/m/Y') }})</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Quantity</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $quantityStr }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Gross Weight</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $grossWeightStr }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Volume</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $volumeStr }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Commodity</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $commodityStr }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- NOTE AREA --}}
            <div style="margin-top: 18px;">
                <div style="font-size: 13.5px; font-weight: 700; text-decoration: underline; color: #000; margin-bottom: 6px;">
                    NOTE :
                </div>
                <div style="border: 1px solid #000; min-height: 70px; padding: 12px; font-size: 13px; line-height: 1.5; white-space: pre-wrap; background: #fff; color: #0f172a;">
                    {{ $noteContent ?: '—' }}
                </div>
            </div>
        </div>
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB 2: CUSTOMS & AJU (PERSIS SEPERTI GAMBAR CLIENT)                        --}}
{{-- ========================================================================= --}}
<div id="tab-customs" class="job-tab-content" style="display: none;">
    <section class="panel" style="padding: 24px; margin-bottom: 24px;">
        <div class="panel-heading" style="margin-bottom: 20px;">
            <h2>Dokumen Kepabeanan & Status Jalur Cukai</h2>
            <span class="subtle">{{ $isImport ? 'Nomor Pengajuan AJU, Nopen, SPJM/SPPB' : 'No AJU 6 digit terakhir, NOPEN PEB, tanggal PEB, dan NPE' }}</span>
        </div>
        @if(auth()->user()->can('update', $job))
            <form class="data-form" method="POST" action="{{ route('jobs.update', $job) }}" style="margin-bottom: 24px;">
                @csrf
                @method('PUT')
                <input type="hidden" name="lock_version" value="{{ old('lock_version', $job->lock_version) }}">
                <input type="hidden" name="subject" value="{{ old('subject', $job->subject) }}">
                <input type="hidden" name="job_date" value="{{ old('job_date', $job->job_date?->format('Y-m-d')) }}">
                <input type="hidden" name="redirect_tab" value="customs">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="field">
                        <label for="booking_reference_customs" style="font-size: 13.5px; font-weight: 700; color: #0f172a;">No AJU (6 digit terakhir)</label>
                        <input type="text" name="booking_reference" id="booking_reference_customs" maxlength="60" value="{{ old('booking_reference', $job->booking_reference) }}" placeholder="contoh: 399308" style="font-size: 16px; font-weight: 600;">
                    </div>
                    @if($isImport)
                        <div class="field">
                            <label for="nopen_customs" style="font-size: 13.5px; font-weight: 700; color: #0f172a;">Nomor Pendaftaran (Nopen)</label>
                            <input type="text" name="nopen" id="nopen_customs" maxlength="60" value="{{ old('nopen', $job->nopen) }}" placeholder="contoh: 508151" style="font-size: 16px; font-weight: 600;">
                        </div>
                    @else
                        <div class="field">
                            <label for="peb_number_customs" style="font-size: 13.5px; font-weight: 700; color: #0f172a;">NOPEN PEB</label>
                            <input type="text" name="peb_number" id="peb_number_customs" maxlength="60" value="{{ old('peb_number', $job->peb_number) }}" placeholder="contoh: 415575" style="font-size: 16px; font-weight: 600;">
                        </div>
                    @endif
                </div>

                @if($isImport)
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="field">
                            <label for="nopen_date_customs" style="font-size: 13px; font-weight: 600; color: #475569;">Tanggal Nopen (SPPB/SPJM)</label>
                            <input type="date" name="nopen_date" id="nopen_date_customs" value="{{ old('nopen_date', $job->nopen_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="field"></div>
                    </div>
                @else
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="field">
                            <label for="peb_date_customs" style="font-size: 13px; font-weight: 600; color: #475569;">Tanggal PEB</label>
                            <input type="date" name="peb_date" id="peb_date_customs" value="{{ old('peb_date', $job->peb_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="field">
                            <label for="npe_number_customs" style="font-size: 13px; font-weight: 600; color: #475569;">Nomor NPE</label>
                            <input type="text" name="npe_number" id="npe_number_customs" maxlength="60" value="{{ old('npe_number', $job->npe_number) }}" placeholder="Isi nomor NPE">
                        </div>
                    </div>
                @endif

                <div class="form-actions" style="margin-bottom: 0; justify-content: flex-end;">
                    <button class="button button-primary">Simpan Data Kepabeanan</button>
                </div>
            </form>
        @else
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div><label style="display: block; font-size: 13.5px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">No AJU (6 digit terakhir)</label><div style="padding: 12px 16px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 16px; font-weight: 600; color: #0f172a; background: #fff;">{{ $noAju }}</div></div>
                <div><label style="display: block; font-size: 13.5px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">{{ $isImport ? 'Nomor Pendaftaran (Nopen)' : 'NOPEN PEB' }}</label><div style="padding: 12px 16px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 16px; font-weight: 600; color: #0f172a; background: #fff;">{{ $isImport ? $noNopen : $noPeb }} @if($isImport && $job->nopen_date)<span style="font-size: 13px; font-weight: normal; color: #64748b;">(Tgl: {{ $nopenDate }})</span>@elseif(!$isImport && $job->peb_date)<span style="font-size: 13px; font-weight: normal; color: #64748b;">(Tgl: {{ $pebDate }})</span>@endif</div></div>
            </div>
        @endif
        {{-- FORM UPDATE STATUS KEPABEANAN --}}
        @if($job->status === 'open' && auth()->user()->can('update', $job))
            <form class="transition-form" method="POST" action="{{ route('jobs.shipment-status',$job) }}" style="padding-top: 16px; border-top: 1px solid #e2e8f0;">
                @csrf
                <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">
                <label for="shipment_status_customs" style="font-weight: 700;">Update Status Kepabeanan / Pengiriman</label>
                @if($isImport)
                    <select name="shipment_status" id="shipment_status_customs">
                        <option value="pib_submitted" @selected($job->shipment_status==='pib_submitted')>PIB Diajukan (Menunggu Billing BC)</option>
                        <option value="billing" @selected($job->shipment_status==='billing')>Billing BC Diterima (Menunggu Penjaluran)</option>
                        <option value="spjm" @selected($job->shipment_status==='spjm')>SPJM — Menunggu Pemeriksaan Fisik</option>
                        <option value="behandle" @selected($job->shipment_status==='behandle')>Pemeriksaan Fisik — Menunggu SPPB</option>
                        <option value="sppb" @selected($job->shipment_status==='sppb')>SPPB Terbit — Selesai</option>
                    </select>
                    <p class="form-help">Alur Import: <strong>PIB</strong> → <strong>Billing BC</strong> → <strong>Penjaluran</strong> (SPPB langsung selesai | SPJM → Pemeriksaan Fisik → SPPB)</p>
                @else
                    <select name="shipment_status" id="shipment_status_customs">
                        @foreach(config('operations.shipment_statuses') as $value=>$label)
                            @continue(in_array($value, ['pib_submitted','billing','spjm','sppb','behandle'], true))
                            <option value="{{ $value }}" @selected($job->shipment_status===$value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="form-help">Pilih status untuk mencatat progress pengiriman export.</p>
                @endif
                <button class="button button-secondary">Simpan Status Kepabeanan</button>
            </form>
        @endif
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB 3: DOKUMEN (BL/CIPL/SI/BC/SK DO/DNP/SK PABEAN)                        --}}
{{-- ========================================================================= --}}
<div id="tab-documents" class="job-tab-content" style="display: none;">
    <section class="panel" style="padding: 24px; margin-bottom: 24px;">
        <div class="panel-heading" style="margin-bottom: 16px;">
            <h2>Dokumen Operasional: {{ $serviceCategoryTitle }}</h2>
            <span class="subtle">Dokumen resmi pengapalan sesuai kategori layanan</span>
        </div>

        {{-- FILE LAMPIRAN BL / CIPL / COO DLL --}}
        <div class="panel-heading" style="margin-bottom: 12px;">
            <h2>File Lampiran Dokumen (BL, Packing List, CIPL, dll)</h2>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Tipe Dokumen</th>
                        <th>Nama File</th>
                        <th>Ukuran</th>
                        <th>Waktu Upload</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($job->documents as $doc)
                        <tr>
                            <td><strong style="font-weight: 600; color: #0f172a;">{{ $doc->documentType->name }}</strong></td>
                            <td><strong>{{ $doc->original_name }}</strong><br><small>Oleh: {{ $doc->uploader?->name ?? 'Sistem' }}</small>@if($doc->notes)<p class="form-help" style="margin-top:4px">{{ $doc->notes }}</p>@endif</td>
                            <td>{{ $doc->file_size_formatted }}</td>
                            <td>{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn-action btn-action-primary" href="{{ route('jobs.documents.download', [$job, $doc]) }}" target="_blank" title="Download Dokumen" data-tooltip="Download" aria-label="Download Dokumen"><x-icon name="download"/></a>
                                    @can('update',$job)
                                        <form method="POST" action="{{ route('jobs.documents.destroy', [$job, $doc]) }}" data-confirm="Hapus dokumen ini?">
                                            @csrf @method('DELETE')
                                            <button class="btn-action btn-action-danger" title="Hapus Dokumen" data-tooltip="Hapus" aria-label="Hapus Dokumen"><x-icon name="trash"/></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <x-icon name="file"/>
                                    <h3>Belum ada lampiran dokumen</h3>
                                    <p>Unggah dokumen PDF maksimal 3 MB sesuai jenis dokumen service.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($isImport && !empty($checklist['items']))
            <div style="margin-top:20px;padding-top:20px;border-top:1px solid #e2e8f0;">
                <div class="panel-heading" style="margin-bottom:12px;">
                    <h2>Status Customs</h2>
                    <span class="subtle">{{ $checklist['status_summary'] }}</span>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;">
                    @foreach($checklist['items'] as $item)
                        <div style="padding:14px;border-radius:14px;border:1px solid {{ $item['completed'] ? '#bbf7d0' : (($item['active'] ?? false) ? '#fca5a5' : '#e2e8f0') }};background:{{ $item['completed'] ? '#f0fdf4' : (($item['active'] ?? false) ? '#fff7ed' : '#f8fafc') }};">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                                <div>
                                    <strong style="display:block;color:#0f172a;font-size:14px;">{{ $item['label'] }}</strong>
                                    <small style="display:block;color:#64748b;margin-top:4px;line-height:1.5;">{{ $item['sublabel'] }}</small>
                                </div>
                                <span style="white-space:nowrap;font-size:11px;font-weight:700;padding:5px 8px;border-radius:999px;background:{{ $item['badge_bg'] }};color:{{ $item['badge_color'] }};">{{ $item['badge_text'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @can('update',$job)
            <form class="data-form" style="margin-top:20px;padding-top:20px;border-top:1px solid #e2e8f0" method="POST" action="{{ route('jobs.documents.store',$job) }}" enctype="multipart/form-data">
                @csrf
                <div class="panel-heading" style="margin-bottom:12px;">
                    <h2>Upload Dokumen Lampiran</h2>
                    <span class="subtle">Unggah file dokumen operasional (BL/AWB, Invoice, Packing List, SPPB, Surat Jalan, dll).</span>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="document_type_id">Jenis Dokumen <span class="required">*</span></label>
                        <select name="document_type_id" id="document_type_id" required>
                            <option value="">Pilih tipe dokumen</option>
                            @foreach($documentTypes as $dt)
                                <option value="{{ $dt->id }}">{{ $dt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="file">File PDF <span class="required">*</span></label>
                        <input type="file" name="file" id="file" required accept="application/pdf,.pdf">
                        <small class="form-help">Hanya PDF, maksimal 3 MB.</small>
                    </div>
                    <div class="field span-2">
                        <label for="notes">Keterangan Tambahan</label>
                        <input type="text" name="notes" id="notes" maxlength="255" placeholder="Opsional">
                    </div>
                </div>
                <div class="form-actions">
                    <button class="button button-primary">Upload Dokumen</button>
                </div>
            </form>
        @endcan
    </section>
</div>

@if($isImport)
{{-- ========================================================================= --}}
{{-- TAB: SURAT KUASA (SK DO & SK PABEAN)                                      --}}
{{-- ========================================================================= --}}
<div id="tab-sk" class="job-tab-content" style="display: none;">
    <section class="panel" style="margin-bottom: 24px;">
        <div class="panel-heading">
            <div style="display:flex;align-items:center;gap:12px;">
                <span class="stat-icon blue"><x-icon name="file"/></span>
                <div>
                    <p class="eyebrow" style="margin-bottom:4px;">DOKUMEN SURAT KUASA</p>
                    <h2>Import (Confirm) — Surat Kuasa</h2>
                    <p>Surat kuasa untuk pengurusan Delivery Order dan kepabeanan import.</p>
                </div>
            </div>
        </div>

        <div class="report-grid" style="padding: 0 24px 24px; align-items: start; gap: 24px;">
            <article class="report-card" style="align-self: start;">
                <div class="report-card-head">
                    <div class="report-card-title">
                        <span class="report-icon blue"><x-icon name="file"/></span>
                        <div>
                            <h2>Surat Kuasa DO (SK DO)</h2>
                            <small>Pengambilan DO di pelayaran / agen</small>
                        </div>
                    </div>
                </div>
                <p style="font-size: 12px; color: #64748b; line-height: 1.8; margin: 14px 0 18px;">Surat kuasa dari consignee/customer kepada PT Radix International Logistics untuk mengurus dan mengambil Delivery Order.</p>
                <a class="button button-primary" href="{{ route('jobs.sk-do.pdf', $job) }}" target="_blank"><x-icon name="file"/> Preview / Cetak SK DO</a>
            </article>

            <article class="report-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div class="report-card-head">
                        <div class="report-card-title">
                            <span class="report-icon purple"><x-icon name="file"/></span>
                            <div>
                                <h2>Surat Kuasa Kepabeanan (SK Pabean)</h2>
                                <small>Pengurusan dokumen & fisik Bea Cukai</small>
                            </div>
                        </div>
                    </div>
                    <p style="font-size: 12px; color: #64748b; line-height: 1.7; margin: 12px 0 16px;">Surat kuasa kepabeanan untuk proses pengeluaran barang impor di kantor pelayanan Bea dan Cukai.</p>

                    @if(auth()->user()->can('update', $job))
                        <form class="data-form" method="POST" action="{{ route('jobs.update', $job) }}" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 16px;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="lock_version" value="{{ old('lock_version', $job->lock_version) }}">
                            <input type="hidden" name="subject" value="{{ old('subject', $job->subject) }}">
                            <input type="hidden" name="job_date" value="{{ old('job_date', $job->job_date?->format('Y-m-d')) }}">
                            <input type="hidden" name="redirect_tab" value="sk">

                            <div style="font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                <x-icon name="file" style="width: 14px; height: 14px; color: #7c3aed;"/>
                                <span>Input Data SK Pabean (Manual)</span>
                            </div>

                            <div class="field" style="margin-bottom: 10px;">
                                <label for="sk_pabean_number" style="font-size: 11.5px; font-weight: 600; color: #475569;">Nomor Surat Kuasa Pabean (Manual)</label>
                                <input type="text" name="sk_pabean_number" id="sk_pabean_number" maxlength="60" value="{{ old('sk_pabean_number', $job->sk_pabean_number) }}" placeholder="Nomor surat manual (opsional, jika kosong default: nomor Job)" style="font-size: 12.5px;">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                <div class="field">
                                    <label for="commercial_invoice_number" style="font-size: 11.5px; font-weight: 600; color: #475569;">Nomor Invoice</label>
                                    <input type="text" name="commercial_invoice_number" id="commercial_invoice_number" maxlength="60" value="{{ old('commercial_invoice_number', $job->commercial_invoice_number) }}" placeholder="No. Invoice" style="font-size: 12.5px;">
                                </div>
                                <div class="field">
                                    <label for="commercial_invoice_date" style="font-size: 11.5px; font-weight: 600; color: #475569;">Tanggal Invoice</label>
                                    <input type="date" name="commercial_invoice_date" id="commercial_invoice_date" value="{{ old('commercial_invoice_date', $job->commercial_invoice_date?->format('Y-m-d')) }}" style="font-size: 12.5px;">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                <div class="field">
                                    <label for="packing_list_number" style="font-size: 11.5px; font-weight: 600; color: #475569;">Nomor Packing List</label>
                                    <input type="text" name="packing_list_number" id="packing_list_number" maxlength="60" value="{{ old('packing_list_number', $job->packing_list_number) }}" placeholder="No. Packing List" style="font-size: 12.5px;">
                                </div>
                                <div class="field">
                                    <label for="packing_list_date" style="font-size: 11.5px; font-weight: 600; color: #475569;">Tanggal Packing List</label>
                                    <input type="date" name="packing_list_date" id="packing_list_date" value="{{ old('packing_list_date', $job->packing_list_date?->format('Y-m-d')) }}" style="font-size: 12.5px;">
                                </div>
                            </div>

                            <div class="field" style="margin-bottom: 10px;">
                                <label for="invoice_issuer" style="font-size: 11.5px; font-weight: 600; color: #475569;">Nama Penerbit Invoice</label>
                                <input type="text" name="invoice_issuer" id="invoice_issuer" maxlength="160" value="{{ old('invoice_issuer', $job->invoice_issuer ?: $job->shipper_name) }}" placeholder="Nama perusahaan shipper/penerbit invoice" style="font-size: 12.5px;">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 10px; margin-bottom: 14px;">
                                <div class="field">
                                    <label for="incoterm" style="font-size: 11.5px; font-weight: 600; color: #475569;">Incoterm</label>
                                    @php
                                        $curIncoterm = strtoupper(old('incoterm', $job->incoterm ?: ($job->quotation?->incoterm ?? 'CIF')));
                                    @endphp
                                    <select name="incoterm" id="incoterm" style="font-size: 12.5px;">
                                        <option value="CIF" @selected($curIncoterm === 'CIF')>CIF</option>
                                        <option value="FOB" @selected($curIncoterm === 'FOB')>FOB</option>
                                        <option value="EXW" @selected($curIncoterm === 'EXW')>EXW</option>
                                        <option value="DDP" @selected($curIncoterm === 'DDP')>DDP</option>
                                        <option value="CFR" @selected($curIncoterm === 'CFR' || $curIncoterm === 'C&F')>CFR / C&F</option>
                                        <option value="FCA" @selected($curIncoterm === 'FCA')>FCA</option>
                                        <option value="CPT" @selected($curIncoterm === 'CPT')>CPT</option>
                                        <option value="CIP" @selected($curIncoterm === 'CIP')>CIP</option>
                                        <option value="DAP" @selected($curIncoterm === 'DAP')>DAP</option>
                                        <option value="DPU" @selected($curIncoterm === 'DPU')>DPU</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="invoice_amount" style="font-size: 11.5px; font-weight: 600; color: #475569;">Nilai Invoice</label>
                                    <input type="text" name="invoice_amount" id="invoice_amount" maxlength="100" value="{{ old('invoice_amount', $job->invoice_amount) }}" placeholder="contoh: USD 25,000 / Rp 150.000.000" style="font-size: 12.5px;">
                                </div>
                            </div>

                            <div style="display: flex; justify-content: flex-end;">
                                <button type="submit" class="button button-primary" style="font-size: 12px; padding: 7px 16px;">
                                    <x-icon name="check"/> Simpan Data SK Pabean
                                </button>
                            </div>
                        </form>
                    @else
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 16px; font-size: 12px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                                <div><span style="color: #64748b;">No. Invoice:</span> <strong>{{ $job->commercial_invoice_number ?: '-' }}</strong></div>
                                <div><span style="color: #64748b;">No. Packing List:</span> <strong>{{ $job->packing_list_number ?: '-' }}</strong></div>
                            </div>
                            <div style="margin-bottom: 8px;">
                                <span style="color: #64748b;">Penerbit Invoice:</span> <strong>{{ $job->invoice_issuer ?: ($job->shipper_name ?: '-') }}</strong>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <div><span style="color: #64748b;">Incoterm:</span> <strong>{{ $job->incoterm ?: ($job->quotation?->incoterm ?? 'CIF') }}</strong></div>
                                <div><span style="color: #64748b;">Nilai Invoice:</span> <strong>{{ $job->invoice_amount ?: '-' }}</strong></div>
                            </div>
                        </div>
                    @endif
                </div>

                <div style="padding-top: 6px;">
                    <a class="button button-primary" href="{{ route('jobs.sk-pabean.pdf', $job) }}" target="_blank" style="width: 100%; justify-content: center;">
                        <x-icon name="file"/> Preview / Cetak SK Pabean
                    </a>
                </div>
            </article>
        </div>
    </section>
</div>
{{-- ========================================================================= --}}
{{-- TAB: DEKLARASI NILAI PABEAN (DNP)                                         --}}
{{-- ========================================================================= --}}
<div id="tab-dnp" class="job-tab-content" style="display: none;">
    <section class="panel" style="padding:0; overflow:hidden; border:1px solid #dbe5f1; box-shadow:0 10px 28px rgba(15,23,42,.06); margin-bottom: 24px;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:20px; padding:24px 28px; background:linear-gradient(135deg,#eff6ff 0%,#ffffff 72%); border-bottom:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:15px;">
                <div style="width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#fef3c7;color:#d97706;font-size:23px;">📋</div>
                <div>
                    <p class="eyebrow" style="margin-bottom:4px;">DOKUMEN KEPABEANAN IMPORT</p>
                    <h2 style="margin:0 0 4px;">Deklarasi Nilai Pabean (DNP)</h2>
                    <p style="margin:0;color:#64748b;">Dokumen deklarasi nilai transaksi pabean resmi untuk kelengkapan dokumen PIB.</p>
                </div>
            </div>
            <a class="button button-primary" href="{{ route('dnps.create', ['job_id' => $job->id]) }}">+ Buat Deklarasi Nilai Pabean (DNP)</a>
        </div>

        @if($job->dnps->isNotEmpty())
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Nomor DNP</th>
                            <th>Tanggal</th>
                            <th>Currency</th>
                            <th>Harga Invoice</th>
                            <th>Biaya Angkut</th>
                            <th>Asuransi</th>
                            <th>Total Nilai (CIF)</th>
                            <th>Pengulangan (F)</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($job->dnps as $dnp)
                            <tr>
                                <td><strong>{{ $dnp->number }}</strong></td>
                                <td>{{ $dnp->dnp_date?->format('d/m/Y') ?? '—' }}</td>
                                <td><span class="badge-pill">{{ $dnp->currency }}</span></td>
                                <td>{{ number_format((float)$dnp->invoice_value, 2) }}</td>
                                <td>{{ number_format((float)$dnp->freight, 2) }}</td>
                                <td>{{ number_format((float)$dnp->insurance, 2) }}</td>
                                <td><strong>{{ number_format((float)$dnp->total_value, 2) }}</strong></td>
                                <td>{{ $dnp->is_repeated_transaction ? 'YA' : 'TIDAK' }}</td>
                                <td>
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <a class="button button-secondary button-sm" href="{{ route('dnps.show', $dnp) }}">Detail</a>
                                        <a class="button button-secondary button-sm" href="{{ route('dnps.edit', $dnp) }}">Edit</a>
                                        <a class="button button-secondary button-sm" href="{{ route('dnps.pdf', $dnp) }}" target="_blank">Cetak PDF</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="margin:28px; padding:42px 24px; text-align:center; border:1px dashed #cbd5e1; border-radius:14px; background:#f8fafc;">
                <div style="width:54px;height:54px;margin:0 auto 14px;border-radius:50%;display:grid;place-items:center;background:#fef3c7;color:#d97706;font-size:25px;">📋</div>
                <h3 style="margin:0 0 7px;color:#0f172a;">Belum ada Deklarasi Nilai Pabean (DNP)</h3>
                <p style="margin:0;color:#64748b;">Buat dokumen DNP dari tab ini agar seluruh data nilai invoice, freight, asuransi, dan dokumen pendukung terhubung dengan Job Order ini.</p>
            </div>
        @endif
    </section>
</div>@endif

{{-- ========================================================================= --}}
{{-- TAB: TANDA TERIMA & DELIVERY                                              --}}
{{-- ========================================================================= --}}
<div id="tab-delivery" class="job-tab-content" style="display: none;">
    <section class="panel" style="margin-bottom: 24px;">
        <div class="panel-heading">
            <div style="display:flex;align-items:center;gap:12px;">
                <span class="stat-icon blue"><x-icon name="file"/></span>
                <div>
                    <p class="eyebrow" style="margin-bottom:4px;">DOKUMEN OPERASIONAL</p>
                    <h2>{{ ($isExportSea || $isExportAir) ? 'Tanda Terima Dokumen' : 'Surat Jalan & Tanda Terima' }}</h2>
                    <p>{{ ($isExportSea || $isExportAir) ? 'Dokumen tanda terima berkas dan serah terima dokumen ekspor.' : 'Dokumen serah terima barang dan konfirmasi pengantaran.' }}</p>
                </div>
            </div>
        </div>

        <div class="report-grid" style="padding: 0 24px 24px; {{ ($isExportSea || $isExportAir) ? 'display: block;' : '' }}">
            @if(!$isExportSea && !$isExportAir)
            <article class="report-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div class="report-card-head">
                        <div class="report-card-title">
                            <span class="report-icon blue"><x-icon name="file"/></span>
                            <div>
                                <h2>Delivery Order</h2>
                                <small>Delivery order pengantaran barang (Import Sea / Laut)</small>
                            </div>
                        </div>
                    </div>
                    <p style="font-size: 12px; color: #64748b; line-height: 1.7; margin: 12px 0 16px;">Pengisian data armada, nomor container fisik, supir, dan alamat tujuan dari master alamat customer.</p>

                    @if(auth()->user()->can('update', $job))
                        <form class="data-form" method="POST" action="{{ route('jobs.update', $job) }}" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 16px;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="lock_version" value="{{ old('lock_version', $job->lock_version) }}">
                            <input type="hidden" name="subject" value="{{ old('subject', $job->subject) }}">
                            <input type="hidden" name="job_date" value="{{ old('job_date', $job->job_date?->format('Y-m-d')) }}">
                            <input type="hidden" name="redirect_tab" value="delivery">

                            <div style="font-size: 12px; font-weight: 700; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                <x-icon name="file" style="width: 14px; height: 14px; color: #2563eb;"/>
                                <span>Input Data Delivery Order (Import Sea / Delivery)</span>
                            </div>

                            {{-- 1. Nomor Container Manual --}}
                            <div class="field" style="margin-bottom: 10px;">
                                <label for="container_number" style="font-size: 11.5px; font-weight: 600; color: #475569;">Nomor Container (Manual)</label>
                                <input type="text" name="container_number" id="container_number" maxlength="120" value="{{ old('container_number', $job->container_number) }}" placeholder="contoh: TCLU 582910-1 / 40HC atau 2x20ft" style="font-size: 12.5px;">
                            </div>

                            {{-- 2. Master Alamat Pengiriman --}}
                            <div class="field" style="margin-bottom: 10px;">
                                <label for="customer_address_id" style="font-size: 11.5px; font-weight: 600; color: #475569; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Pilih Master Alamat Tujuan</span>
                                    @if($job->customer_id)
                                        <a href="{{ route('customer-addresses.index', ['customer_id' => $job->customer_id]) }}" target="_blank" style="color: #2563eb; font-size: 11px; text-decoration: none; font-weight: 500;">+ Kelola Master Alamat</a>
                                    @endif
                                </label>
                                <select name="customer_address_id" id="customer_address_id" style="font-size: 12.5px;" onchange="onDeliveryAddressSelected(this)">
                                    <option value="">— Gunakan Alamat Standar Consignee / Manual —</option>
                                    @foreach($customerAddresses as $ca)
                                        <option value="{{ $ca->id }}" data-address="{{ $ca->address }}" @selected(old('customer_address_id', $job->customer_address_id) == $ca->id)>
                                            {{ $ca->location_name }} — {{ \Illuminate\Support\Str::limit($ca->address, 50) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field" style="margin-bottom: 10px;">
                                <label for="delivery_address" style="font-size: 11.5px; font-weight: 600; color: #475569;">Alamat Lengkap Tujuan Pengiriman</label>
                                <textarea name="delivery_address" id="delivery_address" rows="2" placeholder="Alamat lengkap lokasi bongkar..." style="font-size: 12px;">{{ old('delivery_address', $job->delivery_address ?: ($job->deliveryAddressLocation?->address ?: $job->consignee_address)) }}</textarea>
                            </div>

                            {{-- 3. Vendor Trucking & Supir / Plat Nomor (Otomatis dari Master Vendor Truk) --}}
                            <div class="field" style="margin-bottom: 10px;">
                                <label for="vendor_trucking_id" style="font-size: 11.5px; font-weight: 600; color: #475569; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Vendor Trucking</span>
                                    <a href="{{ route('vendors.index') }}" target="_blank" style="color: #2563eb; font-size: 11px; text-decoration: none; font-weight: 500;">Lihat Vendor</a>
                                </label>
                                <select name="vendor_trucking_id" id="vendor_trucking_id" style="font-size: 12.5px;" onchange="onVendorTruckingSelected(this)">
                                    <option value="">— Pilih Vendor Trucking —</option>
                                    @foreach($truckingVendors as $tv)
                                        <option value="{{ $tv->id }}" @selected(old('vendor_trucking_id', $job->vendor_trucking_id) == $tv->id)>
                                            {{ $tv->name }} ({{ $tv->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field" style="margin-bottom: 10px;">
                                <label for="vendor_truck_id" style="font-size: 11.5px; font-weight: 600; color: #475569;">Pilih Supir & Plat Nomor dari Vendor</label>
                                <select name="vendor_truck_id" id="vendor_truck_id" style="font-size: 12.5px;" onchange="onVendorTruckSelected(this)">
                                    <option value="">— Pilih Armada / Supir —</option>
                                    @if($job->vendorTrucking && $job->vendorTrucking->trucks)
                                        @foreach($job->vendorTrucking->trucks as $trk)
                                            <option value="{{ $trk->id }}" data-plate="{{ $trk->plate_number }}" data-driver="{{ $trk->driver_name }}" data-phone="{{ $trk->driver_phone }}" data-type="{{ $trk->vehicle_type }}" @selected(old('vendor_truck_id', $job->vendor_truck_id) == $trk->id)>
                                                {{ $trk->plate_number }} — {{ $trk->driver_name }} ({{ $trk->vehicle_type ?: 'Truk' }})
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                                <div class="field">
                                    <label for="truck_plate_number" style="font-size: 11.5px; font-weight: 600; color: #475569;">Nomor Truk (Plat Nomor)</label>
                                    <input type="text" name="truck_plate_number" id="truck_plate_number" maxlength="30" value="{{ old('truck_plate_number', $job->truck_plate_number ?: $job->vendorTruck?->plate_number) }}" placeholder="contoh: B 9123 UE" style="font-size: 12.5px; text-transform: uppercase;">
                                </div>
                                <div class="field">
                                    <label for="vehicle_type" style="font-size: 11.5px; font-weight: 600; color: #475569;">Jenis Kendaraan</label>
                                    @php
                                        $currentVehicle = strtoupper(old('vehicle_type', $job->vehicle_type ?: ($job->vendorTruck?->vehicle_type ?? '')));
                                    @endphp
                                    <select name="vehicle_type" id="vehicle_type" style="font-size: 12.5px;">
                                        <option value="">— Pilih Jenis Kendaraan —</option>
                                        <optgroup label="FCL (Full Container Load)">
                                            <option value="TRAILER" @selected($currentVehicle === 'TRAILER')>TRAILER (FCL)</option>
                                        </optgroup>
                                        <optgroup label="LCL (Less than Container Load)">
                                            <option value="FUSO" @selected($currentVehicle === 'FUSO')>FUSO (LCL)</option>
                                            <option value="PICKUP" @selected($currentVehicle === 'PICKUP' || $currentVehicle === 'PICK UP')>PICKUP (LCL)</option>
                                            <option value="BLINDVAN" @selected($currentVehicle === 'BLINDVAN' || $currentVehicle === 'BLIND VAN')>BLINDVAN (LCL)</option>
                                            <option value="CDD" @selected($currentVehicle === 'CDD')>CDD (LCL)</option>
                                            <option value="CDE" @selected($currentVehicle === 'CDE')>CDE (LCL)</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: flex-end;">
                                <button type="submit" class="button button-primary" style="font-size: 12px; padding: 7px 16px;">
                                    <x-icon name="check"/> Simpan Data Delivery Order
                                </button>
                            </div>
                        </form>
                    @else
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 16px; font-size: 12px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                                <div><span style="color: #64748b;">No. Container:</span> <strong>{{ $job->container_number ?: '-' }}</strong></div>
                                <div><span style="color: #64748b;">No. Truk / Plat:</span> <strong>{{ $job->truck_plate_number ?: ($job->vendorTruck?->plate_number ?: '-') }}</strong></div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                                <div><span style="color: #64748b;">Nama Supir:</span> <strong>{{ $job->vendorTruck?->driver_name ?: ($job->driver_name ?: '-') }}</strong></div>
                                <div><span style="color: #64748b;">No. Telepon Supir:</span> <strong>{{ $job->vendorTruck?->driver_phone ?: ($job->driver_phone ?: '-') }}</strong></div>
                            </div>
                            <div>
                                <span style="color: #64748b;">Tujuan Pengiriman:</span> <strong>{{ $job->delivery_address ?: ($job->consignee_address ?: '-') }}</strong>
                            </div>
                        </div>
                    @endif
                </div>
                <div style="padding-top: 6px;">
                    <a class="button button-primary" href="{{ route('jobs.surat-jalan.pdf', $job) }}" target="_blank" style="width: 100%; justify-content: center;">
                        <x-icon name="file"/> Preview / Cetak Delivery Order
                    </a>
                </div>
            </article>
            @endif

            <article class="report-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div class="report-card-head">
                        <div class="report-card-title">
                            <span class="report-icon green"><x-icon name="check"/></span>
                            <div>
                                <h2>{{ ($isExportSea || $isExportAir) ? 'Tanda Terima Dokumen' : 'Tanda Terima' }}</h2>
                                <small>{{ ($isExportSea || $isExportAir) ? 'Bukti serah terima dokumen ekspor' : 'Bukti serah terima dokumen / barang' }}</small>
                            </div>
                        </div>
                    </div>
                    <p style="font-size: 12px; color: #64748b; line-height: 1.8; margin: 14px 0 18px;">Cetak bukti penerimaan dengan detail job, referensi {{ ($isExportSea || $isExportAir) ? 'BL/AWB, daftar berkas ekspor' : 'BL/AWB, daftar dokumen/barang' }}, catatan, dan tanda tangan.</p>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    @if(!$isExportSea && !$isExportAir)
                        <a class="button button-primary" href="{{ route('jobs.tanda-terima.pdf', ['job' => $job, 'type' => 'barang']) }}" target="_blank"><x-icon name="file"/> Cetak TT Barang</a>
                    @endif
                    <a class="button {{ ($isExportSea || $isExportAir) ? 'button-primary' : 'button-secondary' }}" href="{{ route('jobs.tanda-terima.pdf', ['job' => $job, 'type' => 'dokumen']) }}" target="_blank"><x-icon name="file"/> Cetak TT Dokumen</a>
                </div>
            </article>
        </div>

        @if(!$isExportSea && !$isExportAir)
        <div style="padding: 0 24px 24px;">
            <article class="report-card">
                <div class="report-card-head">
                    <div class="report-card-title">
                        <span class="report-icon amber"><x-icon name="briefcase"/></span>
                        <div>
                            <h2>Status Delivery Order (DO) & Penyelesaian Pengantaran</h2>
                            <small>Konfirmasi pengantaran selesai & verifikasi berkas Surat Jalan</small>
                        </div>
                    </div>
                </div>
                <strong class="report-value {{ $job->do_confirmed_at ? 'positive' : 'negative' }}" style="font-size: 18px;">
                    {{ $job->do_confirmed_at ? 'Selesai Dikonfirmasi' : 'Menunggu Penyelesaian Pengantaran' }}
                </strong>

                @php
                    $suratJalanDoc = $job->getSuratJalanDocument();
                @endphp

                @if($suratJalanDoc)
                    <div style="margin-top: 14px; padding: 12px 16px; border-radius: 8px; background: #f0fdf4; border: 1px solid #86efac; font-size: 13px; color: #166534; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <strong>✓ Berkas Surat Jalan Terupload:</strong>
                            <span style="font-weight: 600;">{{ $suratJalanDoc->original_name }}</span>
                            <small style="color: #64748b; margin-left: 6px;">({{ $suratJalanDoc->created_at->format('d/m/Y H:i') }})</small>
                        </div>
                        <a class="button button-secondary button-sm" href="{{ route('jobs.documents.download', [$job, $suratJalanDoc]) }}" target="_blank" style="font-size: 12px; padding: 4px 10px;">
                            <x-icon name="download"/> Unduh Berkas
                        </a>
                    </div>
                @else
                    <div style="margin-top: 14px; padding: 14px 16px; border-radius: 8px; background: #fff5f5; border: 1.5px solid #fca5a5; font-size: 13px; color: #991b1b; line-height: 1.6;">
                        <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; margin-bottom: 6px;">
                            <span style="font-size: 16px;">⚠️</span> Berkas Surat Jalan Wajib Diunggah
                        </div>
                        <p style="margin: 0 0 10px; color: #7f1d1d; font-size: 12.5px;">
                            Wajib mengunggah (upload) berkas Surat Jalan (scan/foto bertanda tangan atau stempel penerima) sebelum mengonfirmasi penyelesaian job.
                        </p>
                        @can('update', $job)
                            <form method="POST" action="{{ route('jobs.surat-jalan.upload', $job) }}" enctype="multipart/form-data" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                @csrf
                                <input type="file" name="surat_jalan_file" accept=".pdf,.jpg,.jpeg,.png" required style="font-size: 12px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                                <button type="submit" class="button button-primary button-sm" style="font-size: 12px; padding: 7px 14px;">
                                    <x-icon name="upload"/> Upload Berkas Sekarang
                                </button>
                            </form>
                        @endcan
                    </div>
                @endif

                @if($job->do_confirmed_at)
                    <p style="font-size: 12px; color: #64748b; line-height: 1.8; margin-top: 10px;">Dikonfirmasi pada {{ $job->do_confirmed_at->format('d/m/Y H:i') }} oleh {{ $job->doConfirmedBy?->name ?? 'Petugas' }}.</p>
                @elseif($job->status === 'open')
                    @can('jobs.confirm-do')
                        <form method="POST" action="{{ route('jobs.confirm-do', $job) }}" enctype="multipart/form-data" data-confirm="Konfirmasi bahwa Delivery Order (DO) telah selesai dan barang telah diterima?" style="margin-top: 16px; padding: 14px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0;">
                            @csrf
                            @if(!$suratJalanDoc)
                                <div class="field" style="margin-bottom: 12px;">
                                    <label for="confirm_surat_jalan_file" style="font-size: 12.5px; font-weight: 700; color: #991b1b; display: block; margin-bottom: 4px;">
                                        Upload Berkas Surat Jalan (Wajib) <span class="required">*</span>
                                    </label>
                                    <input type="file" name="surat_jalan_file" id="confirm_surat_jalan_file" accept=".pdf,.jpg,.jpeg,.png" required style="font-size: 12px; padding: 6px 10px; border: 1px solid #fca5a5; border-radius: 6px; background: #fff; width: 100%;">
                                    <small style="color: #64748b; font-size: 11.5px; display: block; margin-top: 3px;">Lampirkan berkas scan/foto Surat Jalan yang telah ditandatangani.</small>
                                </div>
                            @endif
                            <button class="button button-primary" style="background:#16a34a;border-color:#16a34a">
                                <x-icon name="check"/> Konfirmasi DO Selesai
                            </button>
                        </form>
                    @endcan
                @elseif($job->status === 'draft')
                    <div style="margin-top: 14px; padding: 12px 16px; border-radius: 8px; background: #eff6ff; border: 1px solid #bfdbfe; font-size: 13px; color: #1e40af; line-height: 1.6;">
                        <strong>Perhatian:</strong> Job Order ini saat ini masih berstatus <strong>Draft</strong>. Untuk dapat mengonfirmasi DO selesai dan mencatat biaya pengiriman, Job Order perlu dibuka (diaktifkan) terlebih dahulu.
                    </div>
                    @can('open', $job)
                        <form method="POST" action="{{ route('jobs.open',$job) }}" data-confirm="Buka job ini? Finance dapat mulai mencatat biaya setelah job berstatus Open." style="margin-top: 12px;">
                            @csrf
                            <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">
                            <button class="button button-primary"><x-icon name="check"/> Buka Job Sekarang</button>
                        </form>
                    @endcan
                @endif
            </article>
        </div>
        @endif
    </section>
</div>
{{-- ========================================================================= --}}
{{-- TAB 5: BIAYA & PROFIT                                                     --}}
{{-- ========================================================================= --}}
@if($isExportSea || $isExportAir)
<div id="tab-booking" class="job-tab-content" style="display: none;">
    <section class="panel" style="padding:0; overflow:hidden; border:1px solid #dbe5f1; box-shadow:0 10px 28px rgba(15,23,42,.06);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:20px; padding:24px 28px; background:linear-gradient(135deg,#eff6ff 0%,#ffffff 72%); border-bottom:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:15px;"><div style="width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#dbeafe;color:#1d4ed8;font-size:23px;">▣</div><div><p class="eyebrow" style="margin-bottom:4px;">DOKUMEN OPERASIONAL</p><h2 style="margin:0 0 4px;">Booking Confirmation</h2><p style="margin:0;color:#64748b;">Konfirmasi booking yang terhubung dengan Job Order ini.</p></div></div>
            @if($job->bookingConfirmations->isNotEmpty())
                <span style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;color:#166534;font-size:13px;font-weight:600;padding:6px 14px;border-radius:9999px;border:1px solid #bbf7d0;">
                    ✓ Dokumen Dibuat (Maks 1x)
                </span>
            @else
                <a class="button button-primary" href="{{ route('booking-confirmations.create', ['job_id' => $job->id]) }}">+ Buat Booking Confirmation</a>
            @endif
        </div>
        @if($job->bookingConfirmations->isNotEmpty())
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($job->bookingConfirmations as $bc)
                            <tr>
                                <td><strong>{{ $bc->number }}</strong></td>
                                <td>{{ $bc->booking_date?->format('d/m/Y') ?? '—' }}</td>
                                <td><span class="status-badge">{{ ucfirst($bc->status ?? 'Draft') }}</span></td>
                                <td>
                                    <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                        <a class="button button-secondary button-sm" href="{{ route('booking-confirmations.show', $bc) }}">Detail</a>
                                        <a class="button button-secondary button-sm" href="{{ route('booking-confirmations.edit', $bc) }}">Edit</a>
                                        <a class="button button-secondary button-sm" href="{{ route('booking-confirmations.preview', $bc) }}" target="_blank">Print</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="margin:28px; padding:42px 24px; text-align:center; border:1px dashed #cbd5e1; border-radius:14px; background:#f8fafc;">
                <div style="width:54px;height:54px;margin:0 auto 14px;border-radius:50%;display:grid;place-items:center;background:#e0ecff;color:#2563eb;font-size:25px;">▣</div>
                <h3 style="margin:0 0 7px;color:#0f172a;">Belum ada Booking Confirmation</h3>
                <p style="margin:0 0 16px;color:#64748b;">Dokumen BC yang terhubung dengan Job Order akan tampil di sini.</p>
                <a class="button button-primary" href="{{ route('booking-confirmations.create', ['job_id' => $job->id]) }}">+ Buat Booking Confirmation</a>
            </div>
        @endif
    </section>
</div>
<div id="tab-si" class="job-tab-content" style="display: none;">
    <section class="panel" style="padding:0; overflow:hidden; border:1px solid #dbe5f1; box-shadow:0 10px 28px rgba(15,23,42,.06);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:20px; padding:24px 28px; background:linear-gradient(135deg,#eff6ff 0%,#ffffff 72%); border-bottom:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:15px;"><div style="width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#dbeafe;color:#1d4ed8;font-size:23px;">▤</div><div><p class="eyebrow" style="margin-bottom:4px;">DOKUMEN OPERASIONAL</p><h2 style="margin:0 0 4px;">Shipping Instruction</h2><p style="margin:0;color:#64748b;">Instruksi pengiriman yang terhubung dengan Job Order ini.</p></div></div>
            @if($job->shippingInstructions->isNotEmpty())
                <span style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;color:#166534;font-size:13px;font-weight:600;padding:6px 14px;border-radius:9999px;border:1px solid #bbf7d0;">
                    ✓ Dokumen Dibuat (Maks 1x)
                </span>
            @else
                <a class="button button-primary" href="{{ route('shipping-instructions.create', ['job_id' => $job->id]) }}">+ Buat Shipping Instruction</a>
            @endif
        </div>
        @if($job->shippingInstructions->isNotEmpty())
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Carrier</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($job->shippingInstructions as $si)
                            <tr>
                                <td><strong>{{ $si->number }}</strong></td>
                                <td>{{ $si->to_carrier ?? '—' }}</td>
                                <td>{{ $si->si_date?->format('d/m/Y') ?? '—' }}</td>
                                <td><span class="status-badge">{{ ucfirst($si->status ?? 'Draft') }}</span></td>
                                <td>
                                    <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                        <a class="button button-secondary button-sm" href="{{ route('shipping-instructions.show', $si) }}">Detail</a>
                                        <a class="button button-secondary button-sm" href="{{ route('shipping-instructions.edit', $si) }}">Edit</a>
                                        <a class="button button-secondary button-sm" href="{{ route('shipping-instructions.preview', $si) }}" target="_blank">Print</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="margin:28px; padding:42px 24px; text-align:center; border:1px dashed #cbd5e1; border-radius:14px; background:#f8fafc;">
                <div style="width:54px;height:54px;margin:0 auto 14px;border-radius:50%;display:grid;place-items:center;background:#e0ecff;color:#2563eb;font-size:25px;">▤</div>
                <h3 style="margin:0 0 7px;color:#0f172a;">Belum ada Shipping Instruction</h3>
                <p style="margin:0 0 16px;color:#64748b;">Instruksi pengiriman yang terhubung dengan Job Order akan tampil di sini.</p>
                <a class="button button-primary" href="{{ route('shipping-instructions.create', ['job_id' => $job->id]) }}">+ Buat Shipping Instruction</a>
            </div>
        @endif
    </section>
</div>
@endif

@if($isExportSea)
<div id="tab-bl" class="job-tab-content" style="display: none;">
    <section class="panel" style="padding:0; overflow:hidden; border:1px solid #dbe5f1; box-shadow:0 10px 28px rgba(15,23,42,.06);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:20px; padding:24px 28px; background:linear-gradient(135deg,#eff6ff 0%,#ffffff 72%); border-bottom:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:15px;">
                <div style="width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#e0f2fe;color:#0284c7;font-size:23px;">🚢</div>
                <div>
                    <p class="eyebrow" style="margin-bottom:4px;">DOKUMEN OPERASIONAL EXPORT SEA</p>
                    <h2 style="margin:0 0 4px;">Bill of Lading (B/L)</h2>
                    <p style="margin:0;color:#64748b;">Dokumen kepemilikan muatan laut yang terhubung dengan Job Order ini.</p>
                </div>
            </div>
            @if($job->billsOfLading->isNotEmpty())
                <span style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;color:#166534;font-size:13px;font-weight:600;padding:6px 14px;border-radius:9999px;border:1px solid #bbf7d0;">
                    ✓ Dokumen Dibuat (Maks 1x)
                </span>
            @else
                <a class="button button-primary" href="{{ route('bills-of-lading.create', ['job_id' => $job->id]) }}">+ Buat Bill of Lading (B/L)</a>
            @endif
        </div>
        @if($job->billsOfLading->isNotEmpty())
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>HBL No.</th>
                            <th>Tanggal</th>
                            <th>Carrier</th>
                            <th>Vessel / Voyage</th>
                            <th>MBL No.</th>
                            <th>Status</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($job->billsOfLading as $bl)
                            <tr>
                                <td><strong>{{ $bl->number }}</strong></td>
                                <td>{{ $bl->bl_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $bl->carrier ?? '—' }}</td>
                                <td>{{ $bl->vessel_voyage ?? '—' }}</td>
                                <td>{{ $bl->mbl_number ?? '—' }}</td>
                                <td><span class="status-badge">{{ ucfirst($bl->status ?? 'Draft') }}</span></td>
                                <td>
                                    <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                        <a class="button button-secondary button-sm" href="{{ route('bills-of-lading.show', $bl) }}">Detail</a>
                                        <a class="button button-secondary button-sm" href="{{ route('bills-of-lading.edit', $bl) }}">Edit</a>
                                        <a class="button button-secondary button-sm" href="{{ route('bills-of-lading.preview', [$bl, 'type' => 'draft']) }}" target="_blank">Cetak BL Draft</a>
                                        <a class="button button-secondary button-sm" href="{{ route('bills-of-lading.preview', [$bl, 'type' => 'original']) }}" target="_blank">Cetak BL Original</a>
                                        <a class="button button-secondary button-sm" href="{{ route('bills-of-lading.preview', [$bl, 'type' => 'copy']) }}" target="_blank">Cetak BL Copy</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="margin:28px; padding:42px 24px; text-align:center; border:1px dashed #cbd5e1; border-radius:14px; background:#f8fafc;">
                <div style="width:54px;height:54px;margin:0 auto 14px;border-radius:50%;display:grid;place-items:center;background:#e0f2fe;color:#0284c7;font-size:25px;">🚢</div>
                <h3 style="margin:0 0 7px;color:#0f172a;">Belum ada Bill of Lading (B/L)</h3>
                <p style="margin:0 0 16px;color:#64748b;">Buat dokumen B/L dari tab ini agar seluruh data kapal dan muatan otomatis terisi.</p>
                <a class="button button-primary" href="{{ route('bills-of-lading.create', ['job_id' => $job->id]) }}">+ Buat Bill of Lading (B/L)</a>
            </div>
        @endif
    </section>
</div>
@endif

@if($isExportAir)
<div id="tab-awb" class="job-tab-content" style="display: none;">
    <section class="panel" style="padding:0; overflow:hidden; border:1px solid #dbe5f1; box-shadow:0 10px 28px rgba(15,23,42,.06);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:20px; padding:24px 28px; background:linear-gradient(135deg,#eff6ff 0%,#ffffff 72%); border-bottom:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:15px;">
                <div style="width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#ede9fe;color:#7c3aed;font-size:23px;">✈️</div>
                <div>
                    <p class="eyebrow" style="margin-bottom:4px;">DOKUMEN OPERASIONAL EXPORT AIR</p>
                    <h2 style="margin:0 0 4px;">Air Waybill (AWB)</h2>
                    <p style="margin:0;color:#64748b;">Dokumen pengangkutan udara yang terhubung dengan Job Order ini.</p>
                </div>
            </div>
            @if($job->awbs->isNotEmpty())
                <span style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;color:#166534;font-size:13px;font-weight:600;padding:6px 14px;border-radius:9999px;border:1px solid #bbf7d0;">
                    ✓ Dokumen Dibuat (Maks 1x)
                </span>
            @else
                <a class="button button-primary" href="{{ route('awbs.create', ['job_id' => $job->id]) }}">+ Buat Air Waybill (AWB)</a>
            @endif
        </div>
        @if($job->awbs->isNotEmpty())
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Nomor AWB</th>
                            <th>Tanggal</th>
                            <th>Airlines</th>
                            <th>Flight</th>
                            <th>HAWB No.</th>
                            <th>MAWB No.</th>
                            <th>Status</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($job->awbs as $awb)
                            <tr>
                                <td><strong>{{ $awb->number }}</strong></td>
                                <td>{{ $awb->awb_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $awb->airline ?? '—' }}</td>
                                <td>{{ $awb->flight_number ?? '—' }}</td>
                                <td>{{ $awb->hawb_number ?? '—' }}</td>
                                <td>{{ $awb->mawb_number ?? '—' }}</td>
                                <td><span class="status-badge">{{ ucfirst($awb->status ?? 'Draft') }}</span></td>
                                <td>
                                    <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                        <a class="button button-secondary button-sm" href="{{ route('awbs.show', $awb) }}">Detail</a>
                                        <a class="button button-secondary button-sm" href="{{ route('awbs.edit', $awb) }}">Edit</a>
                                        <a class="button button-secondary button-sm" href="{{ route('awbs.preview', [$awb, 'type' => 'draft']) }}" target="_blank">Cetak Draft</a>
                                        <a class="button button-secondary button-sm" href="{{ route('awbs.preview', [$awb, 'type' => 'hawb']) }}" target="_blank">Cetak HAWB</a>
                                        <a class="button button-secondary button-sm" href="{{ route('awbs.preview', [$awb, 'type' => 'mawb']) }}" target="_blank">Cetak MAWB</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="margin:28px; padding:42px 24px; text-align:center; border:1px dashed #cbd5e1; border-radius:14px; background:#f8fafc;">
                <div style="width:54px;height:54px;margin:0 auto 14px;border-radius:50%;display:grid;place-items:center;background:#ede9fe;color:#7c3aed;font-size:25px;">✈️</div>
                <h3 style="margin:0 0 7px;color:#0f172a;">Belum ada Air Waybill (AWB)</h3>
                <p style="margin:0 0 16px;color:#64748b;">Buat dokumen AWB dari tab ini agar seluruh data penerbangan dan kargo otomatis terisi.</p>
                <a class="button button-primary" href="{{ route('awbs.create', ['job_id' => $job->id]) }}">+ Buat Air Waybill (AWB)</a>
            </div>
        @endif
    </section>
</div>
@endif

<div id="tab-financial" class="job-tab-content" style="display: none;">
    <div class="section-heading"><h2>Estimasi Penawaran</h2><span class="subtle">Snapshot Quotation Asal</span></div>
    @can('financial.view')
        <section class="panel">
            <div class="table-scroll">
                <table class="quote-detail-table">
                    <thead>
                        <tr>
                            <th>Uraian</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th class="money">Estimasi modal</th>
                            <th class="money">Estimasi tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($job->quotation_snapshot['items'] as $item)
                            <tr>
                                <td>{{ $item['description'] }}</td>
                                <td>{{ ucfirst($item['type']) }}</td>
                                <td>{{ \App\Support\Money::format($item['quantity']) }} {{ $item['unit'] }}</td>
                                <td class="money">{{ \App\Support\Money::format($item['total_cost']) }}</td>
                                <td class="money">{{ \App\Support\Money::format($item['total_price']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="summary-box">
                <div class="summary-row"><span>Estimasi profit</span><strong>Rp {{ \App\Support\Money::format($job->quotation_snapshot['totals']['profit']) }}</strong></div>
                <div class="summary-row summary-total"><span>Total sebelum pajak</span><strong>Rp {{ \App\Support\Money::format($job->quotation_snapshot['totals']['subtotal']) }}</strong></div>
            </div>
        </section>
    @else
        <section class="panel">
            <div class="table-scroll">
                <table class="quote-detail-table">
                    <thead>
                        <tr>
                            <th>Uraian</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th class="money">Estimasi tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($job->quotation_snapshot['items'] as $item)
                            <tr>
                                <td>{{ $item['description'] }}</td>
                                <td>{{ ucfirst($item['type']) }}</td>
                                <td>{{ \App\Support\Money::format($item['quantity']) }} {{ $item['unit'] }}</td>
                                <td class="money">{{ \App\Support\Money::format($item['total_price']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="summary-box">
                <div class="summary-row summary-total"><span>Total sebelum pajak</span><strong>Rp {{ \App\Support\Money::format($job->quotation_snapshot['totals']['subtotal']) }}</strong></div>
            </div>
        </section>
    @endcan

    @can('financial.view')
        <div class="section-heading"><h2>Ringkasan keuangan</h2><span class="subtle">Biaya aktual final · Khusus Finance</span></div>
        <section class="panel">
            <div class="cost-summary-body">
                <div class="summary-box">
                    @if($summary['count'] > 0)
                        <div class="summary-row"><span>Total modal aktual (Provision)</span><strong>Rp {{ \App\Support\Money::format($summary['provision_cost']) }}</strong></div>
                        <div class="summary-row"><span>Total tagihan aktual</span><strong>Rp {{ \App\Support\Money::format($summary['provision_sell']) }}</strong></div>
                        <div class="summary-row"><span>Biaya temporary (Reimbursement)</span><strong>Rp {{ \App\Support\Money::format($summary['temporary']) }}</strong></div>
                        <div class="summary-row"><span>Laba (rugi) aktual</span><strong>Rp {{ \App\Support\Money::format($summary['profit']) }}</strong></div>
                        <div class="summary-row summary-total"><span>Margin</span><strong>{{ \App\Support\Money::format($summary['margin']) }}%</strong></div>
                    @else
                        <div class="empty-state">
                            <h3>Belum ada biaya final</h3>
                            <p>Ringkasan keuangan tampil setelah Finance mencatat dan memfinalisasi biaya.</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endcan
</div>

{{-- SCRIPT TOGGLE TAB HORIZONTAL --}}
<script>
function activateJobTab(targetTabId, updateHash = true) {
    const activeButton = document.querySelector(`.job-tab-btn[data-tab="${targetTabId}"]`);
    const target = document.getElementById(targetTabId);

    if (!activeButton || !target) return;

    document.querySelectorAll('.job-tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = 'transparent';
        btn.style.color = '#475569';
        btn.style.fontWeight = '600';
        btn.style.boxShadow = 'none';
    });

    activeButton.classList.add('active');
    activeButton.style.background = '#fff';
    activeButton.style.color = '#0f172a';
    activeButton.style.fontWeight = '700';
    activeButton.style.boxShadow = '0 1px 4px rgba(0,0,0,0.12)';

    document.querySelectorAll('.job-tab-content').forEach(content => {
        content.style.display = 'none';
    });
    target.style.display = 'block';

    if (updateHash) {
        history.replaceState(null, '', `#${targetTabId}`);
    }
}

document.querySelectorAll('.job-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        activateJobTab(this.dataset.tab);
    });
});

const requestedJobTab = window.location.hash.replace('#', '');
if (requestedJobTab) {
    activateJobTab(requestedJobTab, false);
}

function onDeliveryAddressSelected(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const fullAddress = selectedOption ? selectedOption.dataset.address : '';
    const deliveryAddressField = document.getElementById('delivery_address');
    if (fullAddress && deliveryAddressField) {
        deliveryAddressField.value = fullAddress;
    }
}

function onVendorTruckingSelected(selectEl) {
    const vendorId = selectEl.value;
    const truckSelect = document.getElementById('vendor_truck_id');
    if (!truckSelect) return;

    truckSelect.innerHTML = '<option value="">— Memuat armada... —</option>';

    if (!vendorId) {
        truckSelect.innerHTML = '<option value="">— Pilih Armada / Supir —</option>';
        return;
    }

    fetch(`/api/vendors/${vendorId}/trucks`)
        .then(response => response.json())
        .then(data => {
            truckSelect.innerHTML = '<option value="">— Pilih Armada / Supir —</option>';
            data.forEach(truck => {
                const opt = document.createElement('option');
                opt.value = truck.id;
                opt.textContent = `${truck.plate_number} — ${truck.driver_name} (${truck.vehicle_type || 'Truk'})`;
                opt.dataset.plate = truck.plate_number;
                opt.dataset.driver = truck.driver_name;
                opt.dataset.phone = truck.driver_phone || '';
                opt.dataset.type = truck.vehicle_type || '';
                truckSelect.appendChild(opt);
            });
        })
        .catch(err => {
            console.error('Gagal mengambil daftar armada:', err);
            truckSelect.innerHTML = '<option value="">— Gagal memuat data armada —</option>';
        });
}

function onVendorTruckSelected(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    if (!selectedOption || !selectedOption.value) return;

    const plateField = document.getElementById('truck_plate_number');
    const typeField = document.getElementById('vehicle_type');

    if (plateField && selectedOption.dataset.plate) plateField.value = selectedOption.dataset.plate;
    if (typeField && selectedOption.dataset.type) {
        const rawType = selectedOption.dataset.type.toUpperCase().replace(/\s+/g, '');
        for (let opt of typeField.options) {
            if (opt.value && (rawType.includes(opt.value) || opt.value.includes(rawType))) {
                typeField.value = opt.value;
                break;
            }
        }
    }
}
</script>

@endsection
