@extends('layouts.app')
@section('title','Buku Besar')
@section('content')
<x-menu-banner
    tag="LAPORAN AKUNTANSI"
    title="Buku Besar (General Ledger)"
    description="Rincian mutasi transaksi debit-kredit dan riwayat saldo berjalan untuk setiap akun terpilih."
    icon="chart"
    art-title="Mutasi akun,"
    art-subtitle="terinci jelas."
/>

<section class="panel">
    <form class="filter-bar" method="GET">
        <div style="flex: 1; min-width: 280px;">
            <select name="account_id" data-custom-select aria-label="Pilih Akun">
                @foreach($accounts as $item)
                    <option value="{{ $item->id }}" @selected($account->id===$item->id)>{{ $item->code }} — {{ $item->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="date-filter-group">
            <x-icon name="calendar"/>
            <input type="date" name="from" value="{{ $from }}" aria-label="Dari tanggal" title="Dari tanggal">
            <span class="date-sep">→</span>
            <input type="date" name="to" value="{{ $to }}" aria-label="Sampai tanggal" title="Sampai tanggal">
        </div>
        <input name="job_number" value="{{ request('job_number') }}" placeholder="Nomor Job" aria-label="Nomor Job">
        <input name="invoice_number" value="{{ request('invoice_number') }}" placeholder="Nomor Invoice" aria-label="Nomor Invoice">
        <input name="customer" value="{{ request('customer') }}" placeholder="Nama Customer" aria-label="Nama Customer">
        <select name="payment_status" aria-label="Status pembayaran"><option value="">Semua pembayaran</option><option value="paid" @selected(request('payment_status') === 'paid')>Paid</option><option value="unpaid" @selected(request('payment_status') === 'unpaid')>Unpaid</option></select>
        <input name="cost_name" value="{{ request('cost_name') }}" placeholder="Cost Name" aria-label="Cost Name">
        <input name="vendor" value="{{ request('vendor') }}" placeholder="Vendor" aria-label="Vendor">
        <button class="button button-primary">Terapkan</button>
        <a class="button button-secondary" target="_blank" href="{{ route('reports.ledger.pdf', request()->query() + ['account_id' => $account->id, 'from' => $from, 'to' => $to]) }}"><x-icon name="printer"/> Export PDF</a>
    </form>

    <div class="panel-heading">
        <div>
            <h2>{{ $account->code }} — {{ $account->name }}</h2>
            <p>Saldo awal Rp {{ \App\Support\Money::format($opening) }}</p>
        </div>
        <strong>Saldo akhir Rp {{ \App\Support\Money::format($closing) }}</strong>
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jurnal</th>
                    <th>Uraian</th>
                    <th>Job / Invoice</th>
                    <th>Customer / Vendor</th>
                    <th>Faktur Pajak / Bupot</th>
                    <th class="money">Debit</th>
                    <th class="money">Kredit</th>
                    <th class="money">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->journal->journal_date->format('d/m/Y') }}</td>
                        <td>
                            @can('journals.manage')
                                <a class="text-link" href="{{ route('journals.show',$entry->journal) }}">{{ $entry->journal->number }}</a>
                            @else
                                {{ $entry->journal->number }}
                            @endcan
                        </td>
                        <td>{{ $entry->description }}</td>
                        <td><strong>{{ $entry->job_number ?? '—' }}</strong><br><small>{{ $entry->invoice_number ?? '—' }}</small></td>
                        <td>{{ $entry->customer_name ?? '—' }}<br><small>{{ $entry->vendor_name ?? '' }}</small></td>
                        <td>{{ $entry->tax_invoice_number ?? '—' }}<br><small>{{ $entry->withholding_document_number ?? '' }}</small></td>
                        <td class="money">{{ \App\Support\Money::format($entry->debit) }}</td>
                        <td class="money">{{ \App\Support\Money::format($entry->credit) }}</td>
                        <td class="money">{{ \App\Support\Money::format($entry->running_balance) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <x-icon name="chart"/>
                                <h3>Tidak ada mutasi</h3>
                                <p>Tidak ada mutasi pada periode ini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $entries->links() }}</div>
</section>
@endsection
