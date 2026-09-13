@extends('layouts.app')
@section('title','Kalkulator')
@section('content')
<x-menu-banner
    tag="KALKULATOR LOGISTIK"
    title="Kalkulator Muatan & Biaya"
    description="Alat bantu hitung cepat untuk volume, LCL, dan pajak sebelum angka dimasukkan ke quotation atau invoice."
    :action-url="route('calculators.volume-weight')"
    action-label="Hitung CBM"
    action-icon="arrow"
    icon="calculator"
    art-title="Hitung dulu,"
    art-subtitle="baru input angka."
/>
<div class="calculator-cards-grid">
    <a class="calculator-hub-card card-nav" href="{{ route('calculators.volume-weight') }}">
        <div>
            <div class="card-top">
                <div class="calc-icon-badge blue">
                    <x-icon name="chart"/>
                </div>
            </div>
            <h3>Volume Weight &amp; CBM</h3>
        </div>
        <div class="calc-cta-row">
            <span class="calc-cta-arrow"><x-icon name="arrow"/></span>
        </div>
    </a>

    <a class="calculator-hub-card card-nav" href="{{ route('calculators.lcl') }}">
        <div>
            <div class="card-top">
                <div class="calc-icon-badge amber">
                    <x-icon name="briefcase"/>
                </div>
            </div>
            <h3>Estimasi Biaya LCL</h3>
        </div>
        <div class="calc-cta-row">
            <span class="calc-cta-arrow"><x-icon name="arrow"/></span>
        </div>
    </a>

    <a class="calculator-hub-card card-nav" href="{{ route('calculators.tax') }}">
        <div>
            <div class="card-top">
                <div class="calc-icon-badge emerald">
                    <x-icon name="file"/>
                </div>
            </div>
            <h3>Hitung Pajak</h3>
        </div>
        <div class="calc-cta-row">
            <span class="calc-cta-arrow"><x-icon name="arrow"/></span>
        </div>
    </a>
</div>

<style>
.calculator-cards-grid {
    display: grid !important;
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    gap: 22px !important;
    margin-top: 10px !important;
}
.calculator-hub-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 14px !important;
    padding: 24px !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    min-height: 160px !important;
    transition: all 0.22s ease !important;
    text-decoration: none !important;
    color: #1e293b !important;
    box-shadow: 0 4px 18px rgba(15, 31, 61, 0.05) !important;
    position: relative !important;
}
.calculator-hub-card:hover {
    border-color: #cbd5e1 !important;
    transform: translateY(-4px) !important;
    box-shadow: 0 12px 28px rgba(15, 31, 61, 0.12) !important;
}
.calculator-hub-card .card-top {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    margin-bottom: 18px !important;
}
.calc-icon-badge {
    width: 44px !important;
    height: 44px !important;
    border-radius: 12px !important;
    display: inline-grid !important;
    place-items: center !important;
    transition: transform 0.2s ease !important;
}
.calc-icon-badge .icon {
    width: 22px !important;
    height: 22px !important;
}
.calc-icon-badge.blue {
    background: #eff6ff !important;
    color: #2563eb !important;
}
.calc-icon-badge.amber {
    background: #fffbeb !important;
    color: #d97706 !important;
}
.calc-icon-badge.emerald {
    background: #ecfdf5 !important;
    color: #059669 !important;
}
.calculator-hub-card h3 {
    font-size: 16px !important;
    font-weight: 700 !important;
    color: #0f1f3d !important;
    margin: 0 !important;
}
.calc-cta-row {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-end !important;
    padding-top: 14px !important;
    border-top: 1px solid #f1f5f9 !important;
}
.calc-cta-arrow {
    width: 28px !important;
    height: 28px !important;
    border-radius: 50% !important;
    background: #f1f5f9 !important;
    color: #0f1f3d !important;
    display: inline-grid !important;
    place-items: center !important;
    transition: all 0.2s ease !important;
}
.calc-cta-arrow .icon {
    width: 14px !important;
    height: 14px !important;
}
.calculator-hub-card:hover .calc-cta-arrow {
    background: #0f1f3d !important;
    color: #ffffff !important;
    transform: translateX(4px) !important;
}
@media(max-width: 960px) {
    .calculator-cards-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}
@media(max-width: 640px) {
    .calculator-cards-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endsection
