@extends('layouts.app')
@section('title','Kalkulator Volume Weight & CBM')
@section('content')
<div class="page-heading"><div><p class="eyebrow">KALKULATOR</p><h1>Volume Weight &amp; CBM</h1><p>Volume weight memakai divisor 6000 (kargo udara). Berat tagihan = berat kotor terbesar antara berat fisik dan volume weight.</p></div><a class="text-link" href="{{ route('calculators.index') }}">Semua kalkulator</a></div>
<section class="panel form-panel"><form class="data-form calculator-form" id="vw-form">
<div class="form-grid">
<div class="field"><label for="qty">Qty</label><input id="qty" type="number" min="1" value="1"></div>
<div class="field"><label for="length">Panjang (cm)</label><input id="length" type="number" min="0" step="0.01" value="100"></div>
<div class="field"><label for="width">Lebar (cm)</label><input id="width" type="number" min="0" step="0.01" value="80"></div>
<div class="field"><label for="height">Tinggi (cm)</label><input id="height" type="number" min="0" step="0.01" value="60"></div>
<div class="field"><label for="gross_weight">Berat kotor per paket (kg)</label><input id="gross_weight" type="number" min="0" step="0.01" value="40"></div>
</div>
<div class="form-actions"><button class="button button-primary" type="submit">Hitung</button></div>
</form>
<div class="cost-summary-body" id="vw-result" hidden><div class="stats-grid">
<div class="stat-card"><p>Volume weight</p><strong id="r-volume-weight">—</strong></div>
<div class="stat-card"><p>CBM</p><strong id="r-cbm">—</strong></div>
<div class="stat-card"><p>Berat tagihan</p><strong id="r-chargeable">—</strong></div>
</div></div></section>
<script>
(function(){
    const form = document.getElementById('vw-form');
    const result = document.getElementById('vw-result');
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const params = new URLSearchParams({
            'packages[0][qty]': document.getElementById('qty').value,
            'packages[0][length]': document.getElementById('length').value,
            'packages[0][width]': document.getElementById('width').value,
            'packages[0][height]': document.getElementById('height').value,
            'packages[0][gross_weight]': document.getElementById('gross_weight').value,
        });
        fetch('/api/calculators/packages?'+params, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => {
                result.hidden = !ok;
                if (!ok) { alert('Periksa kembali input.'); return; }
                document.getElementById('r-volume-weight').textContent = d.total_volume_weight.toLocaleString('id-ID') + ' kg';
                document.getElementById('r-cbm').textContent = d.total_cbm.toLocaleString('id-ID', { maximumFractionDigits: 4 });
                document.getElementById('r-chargeable').textContent = d.total_chargeable_weight.toLocaleString('id-ID') + ' kg';
            });
    });
})();
</script>
@endsection