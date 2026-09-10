@extends('layouts.app')
@section('title','Kalkulator')
@section('content')
<div class="page-heading"><div><p class="eyebrow">BANTUAN</p><h1>Kalkulator</h1><p>Hitung volume weight, biaya LCL, dan pajak sebelum membuat quotation.</p></div></div>
<section class="panel cards-grid">
    <a class="card-nav" href="{{ route('calculators.volume-weight') }}"><span class="stat-icon blue"><x-icon name="chart"/></span><div><strong>Volume Weight &amp; CBM</strong><p>CBM, volume weight (divisor 6000), dan berat tagihan per paket.</p></div><x-icon name="arrow"/></a>
    <a class="card-nav" href="{{ route('calculators.lcl') }}"><span class="stat-icon amber"><x-icon name="briefcase"/></span><div><strong>Estimasi Biaya LCL</strong><p>Basis W/M: maksimum total m³ vs tonase (1.000 kg) × tarif per m³.</p></div><x-icon name="arrow"/></a>
    <a class="card-nav" href="{{ route('calculators.tax') }}"><span class="stat-icon green"><x-icon name="file"/></span><div><strong>Hitung Pajak</strong><p>PPN, PPh, atau persentase lain dari nilai dasar.</p></div><x-icon name="arrow"/></a>
</section>
@endsection