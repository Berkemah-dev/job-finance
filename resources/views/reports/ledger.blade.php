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
        <input name="pr_number" value="{{ request('pr_number') }}" placeholder="Nomor PR" aria-label="Nomor PR">
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

    @php($isPpnKeluaran = $account->code === '28000')
    @php($isPph23Payable = $account->code === '27000')
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    @if($isPpnKeluaran)
                        <th>No.</th>
                        <th>Tanggal</th>
                        <th>Impor / Ekspor / Domestik</th>
                        <th>Nama Customer</th>
                        <th class="money">Nilai Transaksi</th>
                        <th class="money">Kurs</th>
                        <th class="money">Debit</th>
                        <th class="money">Kredit</th>
                        <th class="money">Saldo</th>
                        <th>Nomor Job</th>
                        <th>Nomor Invoice</th>
                        <th class="money">Tarif PPN Keluaran</th>
                        <th class="money">Nilai Invoice</th>
                        <th class="money">Total PPN Keluaran</th>
                        <th>Nomor Seri Faktur</th>
                    @elseif($isPph23Payable)
                        <th>No.</th>
                        <th>Tanggal</th>
                        <th>No. PR / Jurnal</th>
                        <th>Nama Vendor</th>
                        <th class="money">Nilai Transaksi</th>
                        <th class="money">Debit</th>
                        <th class="money">Kredit</th>
                        <th class="money">Saldo</th>
                        <th>Nomor Job</th>
                        <th class="money">Tarif Hutang PPh 23</th>
                        <th class="money">Nilai Tagihan Vendor</th>
                        <th class="money">Total Hutang PPh 23</th>
                        <th>Nomor Bukti Potong</th>
                    @else
                    <th>Tanggal</th>
                    <th>Jurnal</th>
                    <th>Uraian</th>
                    <th>No. Job</th>
                    <th>No. Invoice</th>
                    <th>Customer / Vendor</th>
                    <th>Faktur Pajak / Bupot</th>
                    <th class="money">Debit</th>
                    <th class="money">Kredit</th>
                    <th class="money">Saldo</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        @if($isPpnKeluaran)
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $entry->journal->journal_date->format('d/m/Y') }}</td>
                            <td>{{ $entry->shipment_category ?? '—' }}</td>
                            <td>{{ $entry->customer_name ?? '—' }}</td>
                            <td class="money">{{ $entry->invoice_value === null ? '—' : (($entry->invoice_currency && $entry->invoice_currency !== 'IDR' ? $entry->invoice_currency.' ' : 'Rp ').\App\Support\Money::format($entry->invoice_value)) }}</td>
                            <td class="money">{{ $entry->invoice_exchange_rate === null ? '—' : \App\Support\Money::format($entry->invoice_exchange_rate) }}</td>
                            <td class="money">{{ \App\Support\Money::format($entry->debit) }}</td>
                            <td class="money">{{ \App\Support\Money::format($entry->credit) }}</td>
                            <td class="money">{{ \App\Support\Money::format($entry->running_balance) }}</td>
                            <td>{{ $entry->job_number ?? '—' }}</td>
                            <td>{{ $entry->invoice_number ?? '—' }}</td>
                            <td class="money">{{ $entry->invoice_tax === null || empty($entry->invoice_value) ? '—' : \App\Support\Money::format(((float) $entry->invoice_tax / (float) $entry->invoice_value) * 100).'%' }}</td>
                            <td class="money">{{ $entry->invoice_value === null ? '—' : \App\Support\Money::format($entry->invoice_value) }}</td>
                            <td class="money">{{ $entry->invoice_tax === null ? '—' : \App\Support\Money::format($entry->invoice_tax) }}</td>
                            <td>{{ $entry->tax_invoice_number ?? 'Belum diisi' }}</td>
                        @elseif($isPph23Payable)
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $entry->journal->journal_date->format('d/m/Y') }}</td>
                            <td>{{ $entry->cost_number ?? $entry->journal->number }}</td>
                            <td>{{ $entry->vendor_name ?? '—' }}</td>
                            <td class="money">{{ $entry->vendor_bill_value === null ? '—' : \App\Support\Money::format($entry->vendor_bill_value) }}</td>
                            <td class="money">{{ \App\Support\Money::format($entry->debit) }}</td>
                            <td class="money">{{ \App\Support\Money::format($entry->credit) }}</td>
                            <td class="money">{{ \App\Support\Money::format($entry->running_balance) }}</td>
                            <td>{{ $entry->job_number ?? '—' }}</td>
                            <td class="money">{{ $entry->withholding_tax_amount === null || empty($entry->vendor_bill_value) ? '—' : \App\Support\Money::format(((float) $entry->withholding_tax_amount / (float) $entry->vendor_bill_value) * 100).'%' }}</td>
                            <td class="money">{{ $entry->vendor_bill_value === null ? '—' : \App\Support\Money::format($entry->vendor_bill_value) }}</td>
                            <td class="money">{{ $entry->withholding_tax_amount === null ? '—' : \App\Support\Money::format($entry->withholding_tax_amount) }}</td>
                            <td>{{ $entry->withholding_document_number ?? 'Belum diisi' }}</td>
                        @else
                        <td>{{ $entry->journal->journal_date->format('d/m/Y') }}</td>
                        <td>
                            @can('journals.manage')
                                <a class="text-link" href="{{ route('journals.show',$entry->journal) }}">{{ $entry->journal->number }}</a>
                            @else
                                {{ $entry->journal->number }}
                            @endcan
                        </td>
                        <td>
                            <div>{{ $entry->description }}</div>
                            @if(!empty($entry->cost_name) && $entry->cost_name !== $entry->description)
                                <small class="subtle" style="color:#64748b;display:block;">Cost: {{ $entry->cost_name }}</small>
                            @endif
                            @if(!empty($entry->cost_number))
                                <small class="subtle" style="color:#2563eb;display:block;">No PR: {{ $entry->cost_number }}</small>
                            @endif
                        </td>
                        <td><strong>{{ $entry->job_number ?? '—' }}</strong></td>
                        <td><strong>{{ $entry->invoice_number ?? '—' }}</strong></td>
                        <td>
                            @if(!empty($entry->customer_name))
                                <span class="partner-customer"><strong>{{ $entry->customer_name }}</strong></span>
                            @endif
                            @if(!empty($entry->vendor_name))
                                <span class="partner-vendor" style="color:#64748b;display:block;font-size:12px;">{{ $entry->vendor_name }}</span>
                            @endif
                            @if(empty($entry->customer_name) && empty($entry->vendor_name))
                                —
                            @endif
                        </td>
                        <td>
                            @if(!empty($entry->tax_invoice_number))
                                <span title="Faktur Pajak" style="font-weight:600;display:block;">NSFP: {{ $entry->tax_invoice_number }}</span>
                            @endif
                            @if(!empty($entry->withholding_document_number))
                                <span title="Bukti Potong" style="color:#059669;display:block;font-size:12px;">Bupot: {{ $entry->withholding_document_number }}</span>
                            @endif
                            @if(empty($entry->tax_invoice_number) && empty($entry->withholding_document_number))
                                —
                            @endif
                        </td>
                        <td class="money">{{ \App\Support\Money::format($entry->debit) }}</td>
                        <td class="money">{{ \App\Support\Money::format($entry->credit) }}</td>
                        <td class="money">{{ \App\Support\Money::format($entry->running_balance) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isPpnKeluaran ? 15 : ($isPph23Payable ? 13 : 10) }}">
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
