@extends('layouts.app')
@section('title', 'Detail Tarif Trucking ' . $truckingPrice->port_origin . ' → ' . $truckingPrice->destination)
@section('content')

@php
    $isSalesOnly = auth()->user()?->hasRole('sales') && ! auth()->user()?->hasRole(['sales-manager', 'super-admin', 'admin']);
    $showTruckingCost = ! $isSalesOnly && (auth()->user()?->hasRole(['sales-manager', 'finance', 'finance-manager', 'super-admin', 'admin']) || auth()->user()?->can('pricing.manage'));
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">PRICING & LOGISTIK / TRUCKING</p>
        <h1>{{ $truckingPrice->port_origin }} → {{ $truckingPrice->destination }}</h1>
        <p>
            @unless($isSalesOnly)
                Vendor: <strong>{{ $truckingPrice->vendor?->name ?? 'Tarif Standar / Umum' }}</strong> ·
            @endunless
            Status: <span class="status-badge {{ $truckingPrice->is_active ? 'status-active' : 'status-inactive' }}">{{ $truckingPrice->is_active ? 'Aktif' : 'Nonaktif' }}</span>
        </p>
    </div>
    <a class="button button-secondary" href="{{ route('pricing.trucking.index') }}">← Kembali</a>
</div>

<div class="quote-actions" style="margin-bottom: 20px;">
    @can('pricing.manage')
        <a class="button button-secondary" href="{{ route('pricing.trucking.edit', $truckingPrice) }}">Edit Tarif Ini</a>
        <form method="POST" action="{{ route('pricing.trucking.toggle', $truckingPrice) }}" data-confirm="{{ $truckingPrice->is_active ? 'Nonaktifkan' : 'Aktifkan' }} tarif ini?" style="display:inline;">
            @csrf
            <input type="hidden" name="lock_version" value="{{ $truckingPrice->lock_version }}">
            <button class="button {{ $truckingPrice->is_active ? 'button-secondary' : 'button-primary' }}">
                {{ $truckingPrice->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
            </button>
        </form>
    @endcan
</div>

{{-- HARGA JUAL TUNGGAL RUTE (20GP / 40FT / 40HQ) --}}
<div style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 1.5px solid #86efac; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 6px rgba(22,163,74,0.08);">
    <div>
        <span style="font-size: 11.5px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">Harga Jual Tunggal Rute (20GP / 40FT / 40HQ)</span>
        <div style="font-size: 26px; font-weight: 800; color: #15803d; margin-top: 4px;">
            {{ $routeSellingPrice ? 'Rp ' . number_format((float) $routeSellingPrice, 0, ',', '.') : 'Belum ditentukan' }}
        </div>
        <p style="margin: 4px 0 0; font-size: 12px; color: #166534;">
            1 harga jual cuman 1 untuk semua tipe kontainer (20GP, 40FT, dan 40HQ).
        </p>
    </div>
    <div style="text-align: right;">
        <span class="status-badge status-active" style="font-size: 12.5px; padding: 4px 12px;">Tarif Rute Aktif</span>
    </div>
</div>

{{-- INFORMASI RUTE & VENDOR --}}
<section class="panel" style="margin-bottom: 24px;">
    <div class="panel-heading">
        <h2>Informasi Rute & Masa Berlaku</h2>
        <span class="subtle">{{ $isSalesOnly ? 'Rincian rute dan masa berlaku' : 'Rincian rute dan vendor armada' }}</span>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; padding: 20px;">
        <div>
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Pelabuhan Asal</span>
            <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 4px;">{{ $truckingPrice->port_origin }}</div>
        </div>
        <div>
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Tujuan / Area</span>
            <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 4px;">{{ $truckingPrice->destination }}</div>
        </div>
        @unless($isSalesOnly)
        <div>
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Jumlah Vendor Penyedia</span>
            <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 4px;">{{ isset($vendorList) ? $vendorList->count() : 1 }} Vendor</div>
        </div>
        @endunless
        <div>
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Masa Berlaku</span>
            <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;">
                {{ $truckingPrice->effective_date?->format('d/m/Y') }}
                @if($truckingPrice->effective_until)
                    <span style="color: #64748b; font-weight: normal;">s/d</span> {{ $truckingPrice->effective_until->format('d/m/Y') }}
                @else
                    <span style="color: #64748b; font-weight: normal;">(Seterusnya)</span>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- LIST MODAL BEBERAPA VENDOR DIDALAMNYA (KHUSUS NON-SALES) --}}
@if($showTruckingCost && isset($vendorList))
<section class="panel" style="margin-bottom: 24px;">
    <div class="panel-heading">
        <div>
            <h2>List Modal Beberapa Vendor (Rute Ini)</h2>
            <p style="margin: 2px 0 0; color: #64748b; font-size: 12.5px;">Daftar perbandingan modal (cost) dari beberapa vendor untuk rute {{ $truckingPrice->port_origin }} → {{ $truckingPrice->destination }}.</p>
        </div>
        @can('pricing.manage')
            <button type="button" class="button button-primary button-sm" onclick="document.getElementById('modal-add-vendor-cost').showModal();">+ Isi / Tambah Modal Vendor</button>
        @endcan
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Nama Vendor Trucking</th>
                    <th>Modal 20GP</th>
                    <th>Modal 40FT</th>
                    <th>Modal 40HQ</th>
                    <th>Status</th>
                    @can('pricing.manage')
                        <th style="text-align: center;">Aksi</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @forelse($vendorList as $vItem)
                    <tr>
                        <td>
                            <strong>{{ $vItem['vendor_name'] }}</strong>
                            @if($vItem['vendor'])
                                <br><small class="muted-cell">{{ $vItem['vendor']->code }} · {{ $vItem['vendor']->phone ?? '—' }}</small>
                            @endif
                        </td>
                        <td>
                            @if($vItem['cost_20gp'])
                                <strong>{{ $vItem['currency'] }} {{ number_format((float) $vItem['cost_20gp'], 0, ',', '.') }}</strong>
                                @if($vItem['cost_20gp_ow'])
                                    <br><small class="muted-cell">OW: {{ $vItem['currency'] }} {{ number_format((float) $vItem['cost_20gp_ow'], 0, ',', '.') }}</small>
                                @endif
                            @else
                                <span class="muted-cell">—</span>
                            @endif
                        </td>
                        <td>
                            @if($vItem['cost_40ft'])
                                <strong>{{ $vItem['currency'] }} {{ number_format((float) $vItem['cost_40ft'], 0, ',', '.') }}</strong>
                                @if($vItem['cost_40ft_ow'])
                                    <br><small class="muted-cell">OW: {{ $vItem['currency'] }} {{ number_format((float) $vItem['cost_40ft_ow'], 0, ',', '.') }}</small>
                                @endif
                            @else
                                <span class="muted-cell">—</span>
                            @endif
                        </td>
                        <td>
                            @if($vItem['cost_40hq'])
                                <strong>{{ $vItem['currency'] }} {{ number_format((float) $vItem['cost_40hq'], 0, ',', '.') }}</strong>
                                @if($vItem['cost_40hq_ow'])
                                    <br><small class="muted-cell">OW: {{ $vItem['currency'] }} {{ number_format((float) $vItem['cost_40hq_ow'], 0, ',', '.') }}</small>
                                @endif
                            @else
                                <span class="muted-cell">—</span>
                            @endif
                        </td>
                        <td>
                            @if($vItem['is_active'])
                                <span class="status-badge status-active">Aktif</span>
                            @else
                                <span class="status-badge status-inactive">Nonaktif</span>
                            @endif
                        </td>
                        @can('pricing.manage')
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 6px; justify-content: center;">
                                    @if($vItem['vendor_id'])
                                        <button type="button" class="button button-secondary button-sm" onclick="openEditVendorCostModal({{ json_encode($vItem) }})">Edit Modal</button>
                                        <form method="POST" action="{{ route('pricing.trucking.delete-vendor-cost') }}" data-confirm="Hapus modal vendor {{ $vItem['vendor_name'] }} dari rute ini?" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="port_origin" value="{{ $truckingPrice->port_origin }}">
                                            <input type="hidden" name="destination" value="{{ $truckingPrice->destination }}">
                                            <input type="hidden" name="vendor_id" value="{{ $vItem['vendor_id'] }}">
                                            <button type="submit" class="button button-secondary button-sm" style="color: #dc2626;">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->can('pricing.manage') ? 6 : 5 }}">
                            <div class="empty-state">
                                <h3>Belum ada modal vendor</h3>
                                <p>Gunakan tombol "+ Isi / Tambah Modal Vendor" untuk menambahkan modal vendor di rute ini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif

{{-- MATRIKS HARGA UTAMA: 20GP / 40FT / 40HQ (NORMAL & OVERWEIGHT) --}}
<section class="panel" style="margin-bottom: 24px;">
    <div class="panel-heading">
        <h2>Matriks Tarif Kontainer (20GP / 40FT / 40HQ)</h2>
        <span class="subtle">Harga Normal & Overweight{{ $showTruckingCost ? ' (Modal dan Harga Jual)' : ' (Harga Jual)' }}</span>
    </div>

    <div class="table-scroll" style="padding: 16px 20px;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="padding: 12px 16px; text-align: left; font-weight: 700; width: 26%;">Tipe Kontainer</th>
                    <th style="padding: 12px 16px; text-align: center; font-weight: 700; width: 37%; background: #f0fdf4; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;">
                        <span style="color: #166534; font-size: 14px;">Muatan Normal</span>
                    </th>
                    <th style="padding: 12px 16px; text-align: center; font-weight: 700; width: 37%; background: #fff7ed;">
                        <span style="color: #9a3412; font-size: 14px;">Muatan Overweight</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($matrix as $key => $row)
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 16px; font-weight: 700; font-size: 14px; color: #0f172a;">
                            <span class="badge-pill" style="font-size: 13px; padding: 4px 10px;">{{ $row['label'] }}</span>
                        </td>
                        {{-- NORMAL --}}
                        <td style="padding: 16px; text-align: center; background: #f0fdf4; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;">
                            @if($row['normal'])
                                @if($showTruckingCost)
                                    <div style="margin-bottom: 4px;">
                                        <span style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Modal (Cost):</span><br>
                                        <strong style="font-size: 14.5px; color: #0f172a;">{{ $row['normal']->currency }} {{ number_format((float) $row['normal']->price, 0, ',', '.') }}</strong>
                                    </div>
                                @endif
                                <div>
                                    <span style="font-size: 11px; color: #16a34a; font-weight: 600; text-transform: uppercase;">Harga Jual:</span><br>
                                    <strong style="font-size: 15px; color: #16a34a;">
                                        {{ $row['normal']->selling_price ? $row['normal']->currency . ' ' . number_format((float) $row['normal']->selling_price, 0, ',', '.') : '—' }}
                                    </strong>
                                </div>
                            @else
                                <span style="color: #94a3b8; font-style: italic; font-size: 13px;">Belum diset</span>
                            @endif
                        </td>
                        {{-- OVERWEIGHT --}}
                        <td style="padding: 16px; text-align: center; background: #fff7ed;">
                            @if($row['overweight'])
                                @if($showTruckingCost)
                                    <div style="margin-bottom: 4px;">
                                        <span style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Modal (Cost):</span><br>
                                        <strong style="font-size: 14.5px; color: #0f172a;">{{ $row['overweight']->currency }} {{ number_format((float) $row['overweight']->price, 0, ',', '.') }}</strong>
                                    </div>
                                @endif
                                <div>
                                    <span style="font-size: 11px; color: #ea580c; font-weight: 600; text-transform: uppercase;">Harga Jual:</span><br>
                                    <strong style="font-size: 15px; color: #ea580c;">
                                        {{ $row['overweight']->selling_price ? $row['overweight']->currency . ' ' . number_format((float) $row['overweight']->selling_price, 0, ',', '.') : '—' }}
                                    </strong>
                                </div>
                            @else
                                <span style="color: #94a3b8; font-style: italic; font-size: 13px;">Belum diset</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@if($routeRates->count() > 0)
<section class="panel">
    <div class="panel-heading">
        <h2>Seluruh Entri Tarif Rute Ini ({{ $truckingPrice->port_origin }} → {{ $truckingPrice->destination }})</h2>
        <span class="subtle">Total {{ $routeRates->count() }} entri tersimpan</span>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Tipe Kontainer</th>
                    <th>Overweight</th>
                    @if($showTruckingCost)<th>Modal (Cost)</th>@endif
                    <th>Harga Jual</th>
                    <th>Tgl Berlaku</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($routeRates as $item)
                    <tr>
                        <td>
                            <span class="badge-pill">{{ \App\Models\ContainerUnit::label($item->container_type) }}</span>
                        </td>
                        <td>
                            <span class="status-badge {{ $item->overweight ? 'status-submitted' : '' }}">
                                {{ $item->overweight ? 'Overweight' : 'Normal' }}
                            </span>
                        </td>
                        @if($showTruckingCost)<td><strong>{{ $item->currency }} {{ number_format((float) $item->price, 0, ',', '.') }}</strong></td>@endif
                        <td><strong style="color: #16a34a;">{{ $item->selling_price ? $item->currency.' '.number_format((float) $item->selling_price, 0, ',', '.') : '—' }}</strong></td>
                        <td>
                            {{ $item->effective_date?->format('d/m/Y') }}
                            @if($item->effective_until)
                                <span class="muted-cell">s/d {{ $item->effective_until->format('d/m/Y') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($item->is_active)
                                <span class="status-badge status-active">Aktif</span>
                            @else
                                <span class="status-badge status-inactive">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            @can('pricing.manage')
                                <div class="table-actions">
                                    <a class="btn-action" href="{{ route('pricing.trucking.edit', $item) }}" title="Edit"><x-icon name="edit"/></a>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif

@can('pricing.manage')
    {{-- Modal Tambah / Isi Modal Vendor --}}
    <dialog id="modal-add-vendor-cost" class="modal-dialog">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #eff6ff; color: #2563eb; font-size: 20px;">
                    🚚
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15.5px; font-weight: 700; color: #0f172a;">Isi Modal Vendor (Rute Ini)</h3>
                    <p style="margin: 2px 0 0; font-size: 11.5px; color: #64748b;">Rute: <strong>{{ $truckingPrice->port_origin }} → {{ $truckingPrice->destination }}</strong></p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-vendor-cost').close()" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: grid; place-items: center; cursor: pointer; font-size: 14px;">✕</button>
        </div>
        <form method="POST" action="{{ route('pricing.trucking.save-vendor-cost') }}" style="padding: 22px 24px;">
            @csrf
            <input type="hidden" name="port_origin" value="{{ $truckingPrice->port_origin }}">
            <input type="hidden" name="destination" value="{{ $truckingPrice->destination }}">
            
            <div style="display: grid; gap: 16px;">
                <div class="field">
                    <label for="add_vendor_id" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Pilih Nama Vendor Trucking <span style="color: #ef4444;">*</span></label>
                    <select id="add_vendor_id" name="vendor_id" required style="width: 100%;">
                        <option value="">-- Pilih Vendor Trucking --</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="border-top: 1px solid #e2e8f0; padding-top: 14px;">
                    <span style="font-size: 12.5px; font-weight: 700; color: #0f172a; display: block; margin-bottom: 10px;">Modal (Cost) Kontainer:</span>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div class="field">
                            <label for="add_cost_20gp" style="font-size: 11.5px; font-weight: 600; color: #475569;">Modal 20GP (Rp)</label>
                            <input type="number" step="0.01" id="add_cost_20gp" name="cost_20gp" placeholder="cth. 3500000">
                        </div>
                        <div class="field">
                            <label for="add_cost_40ft" style="font-size: 11.5px; font-weight: 600; color: #475569;">Modal 40FT (Rp)</label>
                            <input type="number" step="0.01" id="add_cost_40ft" name="cost_40ft" placeholder="cth. 6000000">
                        </div>
                        <div class="field">
                            <label for="add_cost_40hq" style="font-size: 11.5px; font-weight: 600; color: #475569;">Modal 40HQ (Rp)</label>
                            <input type="number" step="0.01" id="add_cost_40hq" name="cost_40hq" placeholder="cth. 6500000">
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px dashed #e2e8f0; padding-top: 14px;">
                    <span style="font-size: 12.5px; font-weight: 700; color: #9a3412; display: block; margin-bottom: 10px;">Modal Overweight (Opsional):</span>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div class="field">
                            <label for="add_cost_20gp_ow" style="font-size: 11.5px; font-weight: 600; color: #475569;">Modal 20GP OW</label>
                            <input type="number" step="0.01" id="add_cost_20gp_ow" name="cost_20gp_ow" placeholder="cth. 4200000">
                        </div>
                        <div class="field">
                            <label for="add_cost_40ft_ow" style="font-size: 11.5px; font-weight: 600; color: #475569;">Modal 40FT OW</label>
                            <input type="number" step="0.01" id="add_cost_40ft_ow" name="cost_40ft_ow" placeholder="cth. 7100000">
                        </div>
                        <div class="field">
                            <label for="add_cost_40hq_ow" style="font-size: 11.5px; font-weight: 600; color: #475569;">Modal 40HQ OW</label>
                            <input type="number" step="0.01" id="add_cost_40hq_ow" name="cost_40hq_ow" placeholder="cth. 7800000">
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="field">
                        <label for="add_selling_price" style="font-size: 11.5px; font-weight: 700; color: #166534;">Harga Jual Rute (Semua Tipe)</label>
                        <input type="number" step="0.01" id="add_selling_price" name="selling_price" value="{{ $routeSellingPrice }}" placeholder="cth. 7500000">
                        <small style="color: #64748b; font-size: 11px;">1 harga jual rute untuk 20GP/40FT/40HQ</small>
                    </div>
                    <div class="field">
                        <label for="add_effective_date" style="font-size: 11.5px; font-weight: 600; color: #475569;">Tanggal Berlaku</label>
                        <input type="date" id="add_effective_date" name="effective_date" value="{{ today()->format('Y-m-d') }}">
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('modal-add-vendor-cost').close()">Batal</button>
                <button type="submit" class="button button-primary">
                    <x-icon name="check"/> Simpan Modal Vendor
                </button>
            </div>
        </form>
    </dialog>

    {{-- Modal Edit Modal Vendor --}}
    <dialog id="modal-edit-vendor-cost" class="modal-dialog">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #eff6ff; color: #2563eb; font-size: 20px;">
                    🚚
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15.5px; font-weight: 700; color: #0f172a;">Edit Modal Vendor</h3>
                    <p id="edit_vendor_subtitle" style="margin: 2px 0 0; font-size: 11.5px; color: #64748b;"></p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-edit-vendor-cost').close()" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: grid; place-items: center; cursor: pointer; font-size: 14px;">✕</button>
        </div>
        <form method="POST" action="{{ route('pricing.trucking.save-vendor-cost') }}" style="padding: 22px 24px;">
            @csrf
            <input type="hidden" name="port_origin" value="{{ $truckingPrice->port_origin }}">
            <input type="hidden" name="destination" value="{{ $truckingPrice->destination }}">
            <input type="hidden" id="edit_vendor_id" name="vendor_id" value="">
            
            <div style="display: grid; gap: 16px;">
                <div style="border-top: 1px solid #e2e8f0; padding-top: 14px;">
                    <span style="font-size: 12.5px; font-weight: 700; color: #0f172a; display: block; margin-bottom: 10px;">Modal (Cost) Kontainer:</span>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div class="field">
                            <label for="edit_cost_20gp" style="font-size: 11.5px; font-weight: 600; color: #475569;">Modal 20GP (Rp)</label>
                            <input type="number" step="0.01" id="edit_cost_20gp" name="cost_20gp">
                        </div>
                        <div class="field">
                            <label for="edit_cost_40ft" style="font-size: 11.5px; font-weight: 600; color: #475569;">Modal 40FT (Rp)</label>
                            <input type="number" step="0.01" id="edit_cost_40ft" name="cost_40ft">
                        </div>
                        <div class="field">
                            <label for="edit_cost_40hq" style="font-size: 11.5px; font-weight: 600; color: #475569;">Modal 40HQ (Rp)</label>
                            <input type="number" step="0.01" id="edit_cost_40hq" name="cost_40hq">
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px dashed #e2e8f0; padding-top: 14px;">
                    <span style="font-size: 12.5px; font-weight: 700; color: #9a3412; display: block; margin-bottom: 10px;">Modal Overweight (Opsional):</span>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div class="field">
                            <label for="edit_cost_20gp_ow" style="font-size: 11.5px; font-weight: 600; color: #475569;">Modal 20GP OW</label>
                            <input type="number" step="0.01" id="edit_cost_20gp_ow" name="cost_20gp_ow">
                        </div>
                        <div class="field">
                            <label for="edit_cost_40ft_ow" style="font-size: 11.5px; font-weight: 600; color: #475569;">Modal 40FT OW</label>
                            <input type="number" step="0.01" id="edit_cost_40ft_ow" name="cost_40ft_ow">
                        </div>
                        <div class="field">
                            <label for="edit_cost_40hq_ow" style="font-size: 11.5px; font-weight: 600; color: #475569;">Modal 40HQ OW</label>
                            <input type="number" step="0.01" id="edit_cost_40hq_ow" name="cost_40hq_ow">
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('modal-edit-vendor-cost').close()">Batal</button>
                <button type="submit" class="button button-primary">
                    <x-icon name="check"/> Simpan Perubahan Modal
                </button>
            </div>
        </form>
    </dialog>

    <script>
        function openEditVendorCostModal(item) {
            document.getElementById('edit_vendor_id').value = item.vendor_id;
            document.getElementById('edit_vendor_subtitle').textContent = 'Vendor: ' + item.vendor_name;
            document.getElementById('edit_cost_20gp').value = item.cost_20gp ? parseFloat(item.cost_20gp) : '';
            document.getElementById('edit_cost_40ft').value = item.cost_40ft ? parseFloat(item.cost_40ft) : '';
            document.getElementById('edit_cost_40hq').value = item.cost_40hq ? parseFloat(item.cost_40hq) : '';
            document.getElementById('edit_cost_20gp_ow').value = item.cost_20gp_ow ? parseFloat(item.cost_20gp_ow) : '';
            document.getElementById('edit_cost_40ft_ow').value = item.cost_40ft_ow ? parseFloat(item.cost_40ft_ow) : '';
            document.getElementById('edit_cost_40hq_ow').value = item.cost_40hq_ow ? parseFloat(item.cost_40hq_ow) : '';
            document.getElementById('modal-edit-vendor-cost').showModal();
        }
    </script>
@endcan

@endsection
