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
        <button class="button button-primary">Terapkan</button>
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
                        <td class="money">{{ \App\Support\Money::format($entry->debit) }}</td>
                        <td class="money">{{ \App\Support\Money::format($entry->credit) }}</td>
                        <td class="money">{{ \App\Support\Money::format($entry->running_balance) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
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
</section>
@endsection
