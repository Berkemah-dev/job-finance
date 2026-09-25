@extends('layouts.app')
@section('title','SOA · '.$customer->name)
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">STATEMENT OF ACCOUNT</p>
        <h1>{{ $customer->code }} · {{ $customer->name }}</h1>
        <p>{{ $customer->address ?? '—' }} @if($customer->tax_number)· NPWP {{ $customer->tax_number }} @endif</p>
    </div>
    <div class="action-group">
        <a class="button button-secondary" href="{{ route('reports.soa.pdf', ['soaCustomer' => $customer->id, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" target="_blank">🖨 Cetak PDF SOA</a>
        <a class="button button-secondary" href="{{ route('reports.soa') }}">← Kembali</a>
    </div>
</div>
<section class="panel">
    <form class="filter-bar" method="GET">
        <input type="date" name="from" value="{{ $from->toDateString() }}" aria-label="Dari tanggal">
        <input type="date" name="to" value="{{ $to->toDateString() }}" aria-label="Sampai tanggal">
        <button class="button button-primary">Terapkan periode</button>
        <a class="text-link" href="{{ route('reports.soa.customer',$customer) }}">Reset</a>
        <span class="filter-count">Periode transaksi: {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</span>
    </form>
    <div class="cost-summary-body">
        <div class="stats-grid">
            <div class="stat-card"><p>Saldo awal</p><strong>Rp {{ \App\Support\Money::format($statement['opening']) }}</strong></div>
            <div class="stat-card"><p>Tagihan periode</p><strong>Rp {{ \App\Support\Money::format($statement['invoiced']) }}</strong></div>
            <div class="stat-card"><p>Pembayaran periode</p><strong>Rp {{ \App\Support\Money::format($statement['paid']) }}</strong></div>
            <div class="stat-card"><p>Saldo akhir</p><strong>Rp {{ \App\Support\Money::format($statement['closing']) }}</strong></div>
        </div>
    </div>
    @php $aged = $statement['aged']; @endphp
    @if($aged['invoices'] > 0)
        <div class="cost-progress">
            <span>Umur piutang — 
                <strong>
                    @foreach(['current'=>'Saat ini','aging_1_30'=>'1-30','aging_31_60'=>'31-60','aging_61_90'=>'61-90','aging_90_plus'=>'>90'] as $key=>$label)
                        @if(! \App\Support\Money::decimal($aged[$key])->isZero())
                            <strong>{{ $label }} hari: Rp {{ \App\Support\Money::format($aged[$key]) }}</strong>
                        @endif
                    @endforeach
                </strong>
            </span>
        </div>
    @endif
    {{-- Fitur Kirim SOA via Email (Disembunyikan sementara) --}}
    @if(false)
    @can('email.manage')
        @php
            $defaultEmails = collect([$customer->email])
                ->merge($customer->contacts->pluck('email'))
                ->filter()
                ->unique()
                ->values()
                ->implode("\n");
        @endphp
        <div class="email-soa-card" style="margin: 0 24px 20px 24px; padding: 18px 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 12px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-grid; place-items: center; width: 34px; height: 34px; background: #e0f2fe; color: #0284c7; border-radius: 8px; font-size: 16px;">✉️</span>
                    <div>
                        <strong style="display: block; font-size: 13px; color: #0f1f3d; font-weight: 600;">Kirim Statement of Account via Email</strong>
                        <span style="display: block; font-size: 11px; color: #64748b; margin-top: 1px;">Kirimkan ringkasan tagihan & pembayaran periode {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }} langsung ke email customer.</span>
                    </div>
                </div>
                @if($customer->contacts->isNotEmpty())
                    <span style="font-size: 11px; color: #64748b; background: #fff; padding: 4px 10px; border-radius: 6px; border: 1px solid #e2e8f0; white-space: nowrap;">
                        👥 {{ $customer->contacts->count() }} Kontak Terdaftar
                    </span>
                @endif
            </div>

            <form method="POST" action="{{ route('reports.soa.email', $customer) }}" style="display: flex; flex-direction: column; gap: 10px;">
                @csrf
                <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                <input type="hidden" name="to" value="{{ $to->toDateString() }}">

                <div>
                    <label for="soa-emails-input" style="display: block; font-size: 11px; font-weight: 600; color: #334155; margin-bottom: 5px;">
                        Alamat Email Tujuan <span style="font-weight: normal; color: #64748b;">(satu email per baris atau pisahkan dengan koma)</span>:
                    </label>
                    <textarea 
                        id="soa-emails-input"
                        name="emails" 
                        rows="2" 
                        maxlength="2000" 
                        placeholder="contoh: finance@customer.com"
                        style="width: 100%; box-sizing: border-box; padding: 10px 14px; font-size: 12px; font-family: inherit; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; color: #1e293b; resize: vertical; line-height: 1.5; outline: none; transition: border-color 0.15s ease;"
                    >{{ old('emails', $defaultEmails) }}</textarea>
                    @error('emails')
                        <div class="field-error" style="color: #dc2626; font-size: 11px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                    <div style="font-size: 11px; color: #64748b;">
                        💡 <em>Email yang dikirimkan berisi rincian saldo awal, mutasi tagihan & pembayaran, umur piutang, dan saldo akhir periode.</em>
                    </div>
                    <button type="submit" class="button button-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; font-size: 12px; border-radius: 8px; cursor: pointer; white-space: nowrap;">
                        <span>✉️ Kirim Lewat Email</span>
                    </button>
                </div>
            </form>
        </div>
    @endcan
    @endif
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nomor</th>
                    <th>Keterangan</th>
                    <th class="money">Debet</th>
                    <th class="money">Kredit</th>
                    <th class="money">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($statement['rows'] as $row)
                    <tr>
                        <td>{{ $row['date']->format('d/m/Y') }}</td>
                        <td>{{ $row['number'] }}</td>
                        <td>
                            @if($row['type']==='payment')
                                <span class="status-badge status-partially_paid">Pembayaran</span>
                            @endif
                            {{ $row['description'] }}
                        </td>
                        <td class="money">@if(\App\Support\Money::decimal($row['debit'])->isPositive())Rp {{ \App\Support\Money::format($row['debit']) }}@endif</td>
                        <td class="money">@if(\App\Support\Money::decimal($row['credit'])->isPositive())Rp {{ \App\Support\Money::format($row['credit']) }}@endif</td>
                        <td class="money"><strong>Rp {{ \App\Support\Money::format($row['balance']) }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <h3>Tidak ada transaksi pada periode ini</h3>
                                <p>Perlebar rentang tanggal untuk melihat invoice dan pembayaran.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $statement['rows']->links() }}</div>
</section>

@if(false)
@if(isset($logs) && $logs->isNotEmpty())
<div class="section-heading" style="margin-top: 24px;">
    <h2>📧 Log Riwayat Pengiriman Email SOA</h2>
    <span class="subtle">Riwayat pengiriman statement of account ke email customer</span>
</div>
<section class="panel">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Waktu Kirim</th>
                    <th>Tujuan (Recipients)</th>
                    <th>Periode SOA</th>
                    <th>Pengirim</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            @if(is_array($log->recipients))
                                {{ implode(', ', $log->recipients) }}
                            @else
                                {{ $log->recipients }}
                            @endif
                        </td>
                        <td>{{ $log->period_from?->format('d/m/Y') }} – {{ $log->period_to?->format('d/m/Y') }}</td>
                        <td>{{ $log->sender?->name ?? 'Sistem' }}</td>
                        <td>
                            @if($log->status === 'sent')
                                <span class="status-badge" style="background:#dcfce7; color:#15803d;">Terkirim</span>
                            @else
                                <span class="status-badge" style="background:#fee2e2; color:#b91c1c;">Gagal</span>
                                @if($log->error_message)
                                    <br><small class="muted-cell" title="{{ $log->error_message }}">{{ Str::limit($log->error_message, 40) }}</small>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif
@endif
@endsection