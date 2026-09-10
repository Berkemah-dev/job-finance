@extends('layouts.app')
@section('title','Kalkulator Pajak')
@section('content')
<div class="page-heading"><div><p class="eyebrow">KALKULATOR</p><h1>Hitung Pajak</h1><p>Hitung nominal pajak (PPN, PPh, dsb.) dari nilai dasar dan persentase.</p></div><a class="text-link" href="{{ route('calculators.index') }}">Semua kalkulator</a></div>
<section class="panel form-panel"><form class="data-form calculator-form" id="tax-form">
<div class="form-grid">
<div class="field"><label for="base_amount">Nilai dasar (IDR)</label><input id="base_amount" type="text" inputmode="decimal" value="10000000"></div>
<div class="field"><label for="rate">Persentase (%)</label><input id="rate" type="number" min="0" max="100" step="0.01" value="11"></div>
</div>
<div class="form-actions"><button class="button button-primary" type="submit">Hitung pajak</button></div>
</form>
<div class="cost-summary-body" id="tax-result" hidden><div class="stats-grid">
<div class="stat-card"><p>Nilai dasar</p><strong id="r-base">—</strong></div>
<div class="stat-card"><p>Pajak</p><strong id="r-tax">—</strong></div>
<div class="stat-card"><p>Total termasuk pajak</p><strong id="r-total">—</strong></div>
</div></div></section>
<script>
(function(){
    const form = document.getElementById('tax-form');
    const result = document.getElementById('tax-result');
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const params = new URLSearchParams({
            base_amount: document.getElementById('base_amount').value,
            rate: document.getElementById('rate').value,
        });
        fetch('/api/calculators/tax?'+params, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => {
                result.hidden = !ok;
                if (!ok) { alert('Periksa kembali input.'); return; }
                const idr = new Intl.NumberFormat('id-ID');
                document.getElementById('r-base').textContent = 'Rp ' + idr.format(d.base);
                document.getElementById('r-tax').textContent = 'Rp ' + idr.format(d.tax) + ' (' + d.rate.toLocaleString('id-ID', { maximumFractionDigits: 2 }) + '%)';
                document.getElementById('r-total').textContent = 'Rp ' + idr.format(d.total);
            });
    });
})();
</script>
@endsection