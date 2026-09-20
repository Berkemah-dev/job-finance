@extends('layouts.app')
@section('title', 'Edit AWB ' . $awb->number)
@section('content')

@php
    $backUrl = $awb->job_id
        ? route('jobs.show', $awb->job_id) . '#tab-awb'
        : route('awbs.show', $awb);
@endphp

<style>
.form-grid .field {
    display: flex;
    flex-direction: column;
}
.form-grid .field label {
    min-height: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 11.5px;
    font-weight: 600;
    color: #334155;
    line-height: 1.3;
}
</style>

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL — EXPORT AIR</p>
        <h1>Edit Air Waybill</h1>
        <p>{{ $awb->number }} · Airline: {{ $awb->airline ?: '—' }}</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

<section class="panel form-panel">
    @if($errors->any())
        <div style="margin-bottom: 20px; padding: 14px 18px; border-radius: 8px; background: #fef2f2; border: 1px solid #f87171; color: #991b1b; font-size: 13px;">
            <strong style="display: block; margin-bottom: 6px; font-size: 14px;">⚠️ Periksa kembali isian form Anda:</strong>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form class="data-form" method="POST" action="{{ route('awbs.update', $awb) }}">
        @csrf
        @method('PUT')

        {{-- 1. INFORMASI JOB ORDER & IDENTITAS AWB --}}
        <div class="form-section-heading">
            <h2>Data AWB & Job Order</h2>
            <p>Identitas dokumen Air Waybill dan keterkaitannya dengan Job Order.</p>
        </div>

        <div class="form-grid">
            <div class="field span-2">
                <label for="job_id">Job No.</label>
                <select id="job_id" name="job_id">
                    <option value="">-- Pilih Job Order (Opsional) --</option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}" @selected(old('job_id', $awb->job_id) == $j->id)>
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->subject, 35) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="number">Nomor AWB (Internal) <span class="required">*</span></label>
                <input id="number" name="number" maxlength="60" value="{{ old('number', $awb->number) }}" required>
            </div>

            <div class="field">
                <label for="awb_date">AWB Date <span class="required">*</span></label>
                <input id="awb_date" name="awb_date" type="date" value="{{ old('awb_date', $awb->awb_date?->format('Y-m-d')) }}" required>
            </div>

            <div class="field">
                <label for="hawb_number">HAWB No.</label>
                <input id="hawb_number" name="hawb_number" maxlength="100" value="{{ old('hawb_number', $awb->hawb_number) }}" placeholder="House Air Waybill No.">
            </div>

            <div class="field">
                <label for="mawb_number">MAWB No.</label>
                <input id="mawb_number" name="mawb_number" maxlength="100" value="{{ old('mawb_number', $awb->mawb_number) }}" placeholder="Master Air Waybill No.">
            </div>

            <div class="field">
                <label for="freight_term">Freight <span class="required">*</span></label>
                <select id="freight_term" name="freight_term" required>
                    <option value="PREPAID" @selected(old('freight_term', $awb->freight_term) === 'PREPAID')>PREPAID</option>
                    <option value="COLLECT" @selected(old('freight_term', $awb->freight_term) === 'COLLECT')>COLLECT</option>
                </select>
            </div>

            <div class="field">
                <label for="currency">Currency</label>
                <select id="currency" name="currency">
                    <option value="USD" @selected(old('currency', $awb->currency ?? 'USD') === 'USD')>USD</option>
                    <option value="IDR" @selected(old('currency', $awb->currency) === 'IDR')>IDR</option>
                    <option value="SGD" @selected(old('currency', $awb->currency) === 'SGD')>SGD</option>
                    <option value="EUR" @selected(old('currency', $awb->currency) === 'EUR')>EUR</option>
                </select>
            </div>

            <div class="field">
                <label for="exchange_rate">Exc. Rate (Kurs)</label>
                <input id="exchange_rate" name="exchange_rate" inputmode="decimal" value="{{ old('exchange_rate', $awb->exchange_rate ?? '1.0000') }}">
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="draft" @selected(old('status', $awb->status) === 'draft')>Draft</option>
                    <option value="issued" @selected(old('status', $awb->status) === 'issued')>Issued</option>
                    <option value="completed" @selected(old('status', $awb->status) === 'completed')>Completed</option>
                    <option value="cancelled" @selected(old('status', $awb->status) === 'cancelled')>Cancelled</option>
                </select>
            </div>

            <div class="field span-2">
                <label for="customer_id">Customer (Pemilik Muatan)</label>
                <select id="customer_id" name="customer_id">
                    <option value="">Pilih Customer (Opsional)</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $awb->customer_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- 2. PARTIES --}}
        <div class="form-section-heading">
            <h2>Parties (Shipper & Consignee HAWB/MAWB)</h2>
            <p>Pihak pengirim, penerima, dan agen pengangkutan udara.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="shipper_on_hawb">Shipper on HAWB</label>
                <input id="shipper_on_hawb" name="shipper_on_hawb" maxlength="160"
                    value="{{ old('shipper_on_hawb', $awb->shipper_on_hawb) }}" placeholder="Shipper pada House AWB">
            </div>

            <div class="field">
                <label for="shipper_on_mawb">Shipper on MAWB</label>
                <input id="shipper_on_mawb" name="shipper_on_mawb" maxlength="160"
                    value="{{ old('shipper_on_mawb', $awb->shipper_on_mawb) }}" placeholder="Forwarder / Shipper pada Master AWB">
            </div>

            <div class="field">
                <label for="consignee_on_hawb">Consignee on HAWB</label>
                <input id="consignee_on_hawb" name="consignee_on_hawb" maxlength="160"
                    value="{{ old('consignee_on_hawb', $awb->consignee_on_hawb) }}" placeholder="Consignee pada House AWB">
            </div>

            <div class="field">
                <label for="consignee_on_mawb">Consignee on MAWB</label>
                <input id="consignee_on_mawb" name="consignee_on_mawb" maxlength="160"
                    value="{{ old('consignee_on_mawb', $awb->consignee_on_mawb) }}" placeholder="Agent Tujuan pada Master AWB">
            </div>

            <div class="field">
                <label for="notify_party">Notify Party</label>
                <input id="notify_party" name="notify_party" maxlength="255"
                    value="{{ old('notify_party', $awb->notify_party) }}" placeholder="Pihak yang dinotifikasi">
            </div>

            <div class="field">
                <label for="agent_name">Agent (Destination Agent)</label>
                <select id="agent_name" name="agent_name" data-custom-select data-allow-custom="true" aria-label="Agent (Destination Agent)">
                    <option value="">Pilih Destination Agent atau ketik...</option>
                    @php
                        $currentAgent = old('agent_name', $awb->agent_name);
                        $agentFound = false;
                    @endphp
                    @foreach($airlines as $agent)
                        @if($currentAgent === $agent->name)
                            @php $agentFound = true; @endphp
                        @endif
                        <option value="{{ $agent->name }}" @selected($currentAgent === $agent->name)>
                            {{ $agent->code ? '['.$agent->code.'] ' : '' }}{{ $agent->name }}
                        </option>
                    @endforeach
                    @if($currentAgent && !$agentFound)
                        <option value="{{ $currentAgent }}" selected data-custom-option="true">{{ $currentAgent }}</option>
                    @endif
                </select>
                <small style="color:#64748b;font-size:12px;margin-top:2px;">Vendor / Destination Agent</small>
            </div>
        </div>

        {{-- 3. FLIGHT & ROUTING --}}
        <div class="form-section-heading">
            <h2>Flight & Routing Information</h2>
            <p>Jadwal penerbangan, bandara asal, transit, dan tujuan.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="flight_number">Flight (No. Penerbangan 1)</label>
                <input id="flight_number" name="flight_number" maxlength="60"
                    value="{{ old('flight_number', $awb->flight_number) }}" placeholder="contoh: SQ 957">
            </div>

            <div class="field">
                <label for="flight_date">Date (Tgl Penerbangan 1)</label>
                <input id="flight_date" name="flight_date" type="date" value="{{ old('flight_date', $awb->flight_date?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="connecting_flight">Conn. Flight (Penerbangan Lanjutan)</label>
                <input id="connecting_flight" name="connecting_flight" maxlength="60"
                    value="{{ old('connecting_flight', $awb->connecting_flight) }}" placeholder="contoh: SQ 802">
            </div>

            <div class="field">
                <label for="connecting_flight_date">Date (Tgl Penerbangan Lanjutan)</label>
                <input id="connecting_flight_date" name="connecting_flight_date" type="date" value="{{ old('connecting_flight_date', $awb->connecting_flight_date?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="etd">ETD (Keberangkatan)</label>
                <input id="etd" name="etd" type="date" value="{{ old('etd', $awb->etd?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="eta">ETA (Kedatangan)</label>
                <input id="eta" name="eta" type="date" value="{{ old('eta', $awb->eta?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="airport_of_departure">AOL (Airport of Loading)</label>
                <input id="airport_of_departure" name="airport_of_departure" maxlength="120"
                    value="{{ old('airport_of_departure', $awb->airport_of_departure) }}" placeholder="Bandara Asal">
            </div>

            <div class="field">
                <label for="airport_of_destination">AOD (Airport of Destination)</label>
                <input id="airport_of_destination" name="airport_of_destination" maxlength="120"
                    value="{{ old('airport_of_destination', $awb->airport_of_destination) }}" placeholder="Bandara Tujuan">
            </div>

            <div class="field">
                <label for="transit_airport">Transit (Bandara Transit)</label>
                <input id="transit_airport" name="transit_airport" maxlength="120"
                    value="{{ old('transit_airport', $awb->transit_airport) }}" placeholder="Bandara Transit">
            </div>

            <div class="field">
                <label for="airline">Airlines (Maskapai)</label>
                <select id="airline" name="airline" data-custom-select data-allow-custom="true" aria-label="Airlines (Maskapai)">
                    <option value="">Pilih Maskapai Penerbangan atau ketik...</option>
                    @php
                        $currentAirline = old('airline', $awb->airline);
                        $airlineFound = false;
                    @endphp
                    @foreach($airlines as $a)
                        @if($currentAirline === $a->name)
                            @php $airlineFound = true; @endphp
                        @endif
                        <option value="{{ $a->name }}" data-code="{{ $a->code }}" @selected($currentAirline === $a->name)>
                            {{ $a->code ? '['.$a->code.'] ' : '' }}{{ $a->name }}
                        </option>
                    @endforeach
                    @if($currentAirline && !$airlineFound)
                        <option value="{{ $currentAirline }}" selected data-custom-option="true">{{ $currentAirline }}</option>
                    @endif
                </select>
            </div>

            <div class="field">
                <label for="airline_code">IATA Code</label>
                <input id="airline_code" name="airline_code" maxlength="20"
                    value="{{ old('airline_code', $awb->airline_code) }}" placeholder="contoh: SQ / GA">
            </div>

            <div class="field">
                <label for="account_number">Account Number</label>
                <input id="account_number" name="account_number" maxlength="100"
                    value="{{ old('account_number', $awb->account_number) }}">
            </div>

            <div class="field">
                <label for="value_of_carriage">Value of Carriage</label>
                <input id="value_of_carriage" name="value_of_carriage" maxlength="60"
                    value="{{ old('value_of_carriage', $awb->value_of_carriage ?? 'N.V.D.') }}">
            </div>

            <div class="field">
                <label for="value_of_customs">Value of Customs</label>
                <input id="value_of_customs" name="value_of_customs" maxlength="60"
                    value="{{ old('value_of_customs', $awb->value_of_customs ?? 'N.C.V.') }}">
            </div>
        </div>

        {{-- 4. CARGO DETAILS --}}
        <div class="form-section-heading">
            <h2>Data Muatan Kargo (Cargo Details)</h2>
            <p>Rincian jumlah koli, berat, dan dimensi muatan udara.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="pieces">No. of Pieces</label>
                <input id="pieces" name="pieces" type="number" min="0"
                    value="{{ old('pieces', $awb->pieces) }}" placeholder="Jumlah Pieces / Koli">
            </div>

            <div class="field">
                <label for="gross_weight">Gross Weight</label>
                <div style="display:flex; gap:8px; align-items:center; width:100%;">
                    <input id="gross_weight" name="gross_weight" inputmode="decimal"
                        value="{{ old('gross_weight', $awb->gross_weight) }}" placeholder="0.00" style="flex: 1 1 auto; width: 100%; min-width: 0;">
                    <select id="gross_weight_unit" name="gross_weight_unit" data-native-select style="flex: 0 0 85px; width: 85px; min-width: 85px; max-width: 85px; padding-left: 10px; padding-right: 26px; cursor: pointer; text-align: center; font-weight: 600;">
                        <option value="KGS" @selected(old('gross_weight_unit', $awb->gross_weight_unit ?? 'KGS') === 'KGS')>KGS</option>
                        <option value="LBS" @selected(old('gross_weight_unit', $awb->gross_weight_unit) === 'LBS')>LBS</option>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="chargeable_weight">Chargeable Weight (KGS)</label>
                <input id="chargeable_weight" name="chargeable_weight" inputmode="decimal"
                    value="{{ old('chargeable_weight', $awb->chargeable_weight) }}" placeholder="contoh: 72.00">
            </div>

            <div class="field">
                <label for="volume">Measurement / Volume (CBM)</label>
                <input id="volume" name="volume" inputmode="decimal"
                    value="{{ old('volume', $awb->volume) }}" placeholder="contoh: 0.50">
            </div>

            <div class="field span-2">
                <label for="commodity">Nature and Quantity of Goods (Commodity)</label>
                <textarea id="commodity" name="commodity" rows="3"
                    placeholder="Uraian barang komoditas">{{ old('commodity', $awb->commodity) }}</textarea>
            </div>

            <div class="field span-2">
                <label for="remarks">Handling Information / Remarks</label>
                <textarea id="remarks" name="remarks" rows="2" placeholder="Instruksi penanganan khusus">{{ old('remarks', $awb->remarks) }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ $backUrl }}">Batal</a>
            <button class="button button-primary">Perbarui Air Waybill</button>
        </div>
    </form>
</section>

<script>
document.getElementById('airline')?.addEventListener('change', function() {
    const selectedOpt = this.options[this.selectedIndex];
    const codeInput = document.getElementById('airline_code');
    if (selectedOpt && selectedOpt.dataset.code && codeInput && !codeInput.value) {
        codeInput.value = selectedOpt.dataset.code;
    }
});
</script>

@endsection
