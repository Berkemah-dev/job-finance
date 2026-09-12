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
        <a class="text-link" href="{{ route('reports.soa') }}">← Kembali ke daftar</a>
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
    @can('email.manage')
        <div class="cost-summary-body">
            <p class="panel-note">Kirim statement ini ke email customer (termasuk kontak shipper / consignee). Satu alamat per baris.</p>
            <form method="POST" action="{{ route('reports.soa.email',$customer) }}">
                @csrf
                <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                <input type="hidden" name="to" value="{{ $to->toDateString() }}">
                <textarea name="emails" rows="3" maxlength="2000" placeholder="email@customer.com">{{ $customer->email }}{{ $customer->contacts->pluck('email')->filter()->unique()->map(fn ($email) => '
'.$email)->join('') }}</textarea>
                @error('emails')<div class="info-note">{{ $message }}</div>@enderror
                <div class="action-group" style="margin-top: 10px;">
                    <button class="button button-primary">Kirim lewat email</button>
                </div>
            </form>
        </div>
    @endcan
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
</section>

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
@endsection