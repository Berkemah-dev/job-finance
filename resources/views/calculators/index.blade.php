@extends('layouts.app')
@section('title','Kalkulator')
@section('content')
<x-menu-banner
    tag="KALKULATOR LOGISTIK"
    title="Kalkulator Muatan & Biaya"
    description="Hitung volume weight (CBM), estimasi muatan LCL, serta kalkulasi pajak secara instan dan presisi."
    :action-url="route('calculators.volume-weight')"
    action-label="Mulai Hitung CBM"
    action-icon="arrow"
    icon="calculator"
    art-title="Simulasi muatan,"
    art-subtitle="cepat & akurat."
/>
<div class="calculator-cards-grid">
    <a class="calculator-hub-card card-nav" href="{{ route('calculators.volume-weight') }}">
        <div>
            <div class="card-top">
                <div class="calc-icon-badge blue">
                    <x-icon name="chart"/>
                </div>
                <span class="calc-badge-pill">✈ Udara / Darat</span>
            </div>
            <h3>Volume Weight &amp; CBM</h3>
            <p>Hitung CBM (m³), volume weight udara dengan divisor 6000, dan perbandingan berat tagihan otomatis.</p>
            <div class="calc-features">
                <span class="calc-feature-chip">Divisor 6000</span>
                <span class="calc-feature-chip">CBM m³</span>
                <span class="calc-feature-chip">Chargeable Weight</span>
            </div>
        </div>
        <div class="calc-cta-row">
            <span>Buka Kalkulator Volume</span>
            <span class="calc-cta-arrow"><x-icon name="arrow"/></span>
        </div>
    </a>

    <a class="calculator-hub-card card-nav" href="{{ route('calculators.lcl') }}">
        <div>
            <div class="card-top">
                <div class="calc-icon-badge amber">
                    <x-icon name="briefcase"/>
                </div>
                <span class="calc-badge-pill">🚢 Kargo Laut</span>
            </div>
            <h3>Estimasi Biaya LCL</h3>
            <p>Masukkan detail muatan untuk melihat CBM dan basis W/M. Estimasi biaya LCL akan diinformasikan lebih lanjut oleh Finance/Operational.</p>
            <div class="calc-features">
                <span class="calc-feature-chip">Basis W/M</span>
                <span class="calc-feature-chip">Multi Paket</span>
                <span class="calc-feature-chip">Info biaya menyusul</span>
            </div>
        </div>
        <div class="calc-cta-row">
            <span>Buka Kalkulator LCL</span>
            <span class="calc-cta-arrow"><x-icon name="arrow"/></span>
        </div>
    </a>

    <a class="calculator-hub-card card-nav" href="{{ route('calculators.tax') }}">
        <div>
            <div class="card-top">
                <div class="calc-icon-badge emerald">
                    <x-icon name="file"/>
                </div>
                <span class="calc-badge-pill">💰 Pajak &amp; PPN</span>
            </div>
            <h3>Hitung Pajak</h3>
            <p>Simulasi nominal PPN (11%/12%), PPh, dan persentase tarif kustom dari nilai dasar transaksi logistik.</p>
            <div class="calc-features">
                <span class="calc-feature-chip">PPN Otomatis</span>
                <span class="calc-feature-chip">PPh 23</span>
                <span class="calc-feature-chip">Total Tagihan</span>
            </div>
        </div>
        <div class="calc-cta-row">
            <span>Buka Kalkulator Pajak</span>
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
    justify-content: space-between !important;
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
.calc-badge-pill {
    font-size: 11px !important;
    font-weight: 600 !important;
    padding: 4px 12px !important;
    border-radius: 20px !important;
    background: #f1f5f9 !important;
    color: #475569 !important;
}
.calculator-hub-card h3 {
    font-size: 16px !important;
    font-weight: 700 !important;
    color: #0f1f3d !important;
    margin: 0 0 8px 0 !important;
}
.calculator-hub-card p {
    font-size: 12px !important;
    color: #64748b !important;
    line-height: 1.6 !important;
    margin: 0 0 16px 0 !important;
}
.calc-features {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 6px !important;
    margin-bottom: 22px !important;
}
.calc-feature-chip {
    font-size: 10px !important;
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    color: #475569 !important;
    padding: 3px 9px !important;
    border-radius: 6px !important;
    font-weight: 500 !important;
}
.calc-cta-row {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding-top: 14px !important;
    border-top: 1px solid #f1f5f9 !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #0f1f3d !important;
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
