@extends('layouts.app')
@section('title','Profit per Job')
@section('content')
<x-menu-banner
    tag="ANALISIS PROFITABILITAS"
    title="Analisis Profit per Job"
    description="Evaluasi margin keuntungan, modal aktual, dan nilai jual setiap pekerjaan yang telah closed."
    icon="briefcase"
    art-title="Margin tiap job,"
    art-subtitle="maksimal."
/>
<form class="filter-bar" method="GET"><div class="date-filter-group"><x-icon name="calendar"/><input type="date" name="from" value="{{ $from }}" aria-label="Dari tanggal" title="Dari tanggal"><span class="date-sep">→</span><input type="date" name="to" value="{{ $to }}" aria-label="Sampai tanggal" title="Sampai tanggal"></div><button class="button button-secondary">Terapkan</button></form><section class="panel"><div class="table-scroll"><table><thead><tr><th>Job</th><th>Customer</th><th class="money">Temporary</th><th class="money">Modal</th><th class="money">Nilai jual</th><th class="money">Profit</th><th class="money">Margin</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->job->number }}</td><td>{{ $row->customer_snapshot['name'] }}</td><td class="money">{{ \App\Support\Money::format($row->total_temporary) }}</td><td class="money">{{ \App\Support\Money::format($row->total_provision_cost) }}</td><td class="money">{{ \App\Support\Money::format($row->total_provision_sell) }}</td><td class="money">{{ \App\Support\Money::format($row->profit) }}</td><td class="money">{{ \App\Support\Money::format($row->margin) }}%</td></tr>@empty<tr><td colspan="7">Belum ada job closed pada periode ini.</td></tr>@endforelse</tbody></table></div></section>@endsection
