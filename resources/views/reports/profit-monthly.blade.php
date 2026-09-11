@extends('layouts.app')
@section('title','Profit Bulanan')
@section('content')
<x-menu-banner
    tag="ANALISIS PROFITABILITAS"
    title="Rekap Profit Bulanan"
    description="Tren perolehan profit, omzet penjualan, dan volume pekerjaan dari bulan ke bulan."
    icon="calendar"
    art-title="Pertumbuhan bulanan,"
    art-subtitle="konsisten."
/>

<section class="panel">
    @php
        $months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    @endphp
    <form class="filter-bar" method="GET">
        <div style="min-width: 140px;">
            <select name="year" data-custom-select aria-label="Pilih Tahun">
                @foreach(range(today()->year - 3, today()->year + 1) as $y)
                    <option value="{{ $y }}" @selected($y === $year)>Tahun {{ $y }}</option>
                @endforeach
            </select>
        </div>
        <button class="button button-primary">Terapkan</button>
    </form>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="money">Job</th>
                    <th class="money">Temporary</th>
                    <th class="money">Modal</th>
                    <th class="money">Nilai jual</th>
                    <th class="money">Profit</th>
                    <th class="money">Margin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td><strong>{{ $months[$row['month'] - 1] }} {{ $year }}</strong></td>
                        <td class="money">{{ $row['jobs'] }}</td>
                        <td class="money">{{ \App\Support\Money::format($row['temporary']) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row['cost']) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row['revenue']) }}</td>
                        <td class="money" style="font-weight: 600; color: {{ (float)$row['profit'] >= 0 ? '#16a34a' : '#dc2626' }};">{{ \App\Support\Money::format($row['profit']) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row['margin']) }}%</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="summary-total">
                    <td>Total {{ $year }}</td>
                    <td class="money">{{ $totals['jobs'] }}</td>
                    <td class="money">{{ \App\Support\Money::format($totals['temporary']) }}</td>
                    <td class="money">{{ \App\Support\Money::format($totals['cost']) }}</td>
                    <td class="money">{{ \App\Support\Money::format($totals['revenue']) }}</td>
                    <td class="money" style="color: {{ (float)$totals['profit'] >= 0 ? '#16a34a' : '#dc2626' }};">{{ \App\Support\Money::format($totals['profit']) }}</td>
                    <td class="money">{{ \App\Support\Money::format($totals['margin']) }}%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
@endsection