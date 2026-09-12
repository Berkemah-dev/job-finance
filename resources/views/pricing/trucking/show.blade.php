@extends('layouts.app')
@section('title', 'Detail Tarif Trucking ' . $truckingPrice->port_origin . ' → ' . $truckingPrice->destination)
@section('content')

<div class="page-heading">
    <div>
        <p class="eyebrow">PRICING & LOGISTIK / TRUCKING</p>
        <h1>{{ $truckingPrice->port_origin }} → {{ $truckingPrice->destination }}</h1>
        <p>Vendor: <strong>{{ $truckingPrice->vendor?->name ?? 'Tarif Standar / Umum' }}</strong> · Status: <span class="status-badge {{ $truckingPrice->is_active ? 'status-active' : 'status-inactive' }}">{{ $truckingPrice->is_active ? 'Aktif' : 'Nonaktif' }}</span></p>
    </div>
    <a class="text-link" href="{{ route('pricing.trucking.index') }}">← Kembali ke daftar</a>
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

{{-- INFORMASI RUTE & VENDOR --}}
<section class="panel" style="margin-bottom: 24px;">
    <div class="panel-heading">
        <h2>Informasi Rute & Masa Berlaku</h2>
        <span class="subtle">Rincian rute dan vendor armada</span>
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
        <div>
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Vendor Trucking</span>
            <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 4px;">{{ $truckingPrice->vendor?->name ?? 'Tarif Umum (Tanpa Vendor)' }}</div>
        </div>
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

{{-- MATRIKS HARGA UTAMA: 20GP / 40FT / 40HQ (NORMAL & OVERWEIGHT) --}}
<section class="panel" style="margin-bottom: 24px;">
    <div class="panel-heading">
        <h2>Matriks Tarif Kontainer (20GP / 40FT / 40HQ)</h2>
        <span class="subtle">Harga Normal & Overweight (Modal dan Harga Jual)</span>
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
                                <div style="margin-bottom: 4px;">
                                    <span style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Modal (Cost):</span><br>
                                    <strong style="font-size: 14.5px; color: #0f172a;">{{ $row['normal']->currency }} {{ number_format((float) $row['normal']->price, 0, ',', '.') }}</strong>
                                </div>
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
                                <div style="margin-bottom: 4px;">
                                    <span style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Modal (Cost):</span><br>
                                    <strong style="font-size: 14.5px; color: #0f172a;">{{ $row['overweight']->currency }} {{ number_format((float) $row['overweight']->price, 0, ',', '.') }}</strong>
                                </div>
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
                    <th>Modal (Cost)</th>
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
                        <td><strong>{{ $item->currency }} {{ number_format((float) $item->price, 0, ',', '.') }}</strong></td>
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

@endsection
