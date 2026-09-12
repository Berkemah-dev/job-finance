@extends('layouts.app')
@section('title','Kalkulator Estimasi Biaya LCL')
@section('content')
<div class="page-heading"><div><p class="eyebrow">KALKULATOR</p><h1>Estimasi Biaya LCL</h1><p>Hitung estimasi awal berdasarkan basis W/M. Nilai akhir dan tarif LCL akan diinformasikan lebih lanjut oleh tim Finance/Operational.</p></div><a class="text-link" href="{{ route('calculators.index') }}">Semua kalkulator</a></div>
<section class="panel form-panel"><div class="info-note"><strong>Estimasi biaya LCL akan diinformasikan lebih lanjut.</strong><br>Hasil di bawah hanya membantu menghitung CBM, berat kotor, dan basis W/M. Biaya final mengikuti tarif vendor, rute, dan konfirmasi Finance/Operational.</div><form class="data-form calculator-form" id="lcl-form">
<div class="form-grid">
<div class="field span-2"><label for="rate_per_cbm">Tarif per m³ (IDR)</label><input id="rate_per_cbm" type="text" inputmode="decimal" placeholder="Kosongkan pakai default {{ number_format((float) config('operations.lcl.default_rate'), 0, ',', '.') }}"></div>
</div>
<div id="lcl-rows"><div class="calculator-row form-grid">
<div class="field"><label>Qty</label><input type="number" class="row-qty" min="1" value="1"></div>
<div class="field"><label>Panjang (cm)</label><input type="number" class="row-length" min="0" step="0.01" value="120"></div>
<div class="field"><label>Lebar (cm)</label><input type="number" class="row-width" min="0" step="0.01" value="100"></div>
<div class="field"><label>Tinggi (cm)</label><input type="number" class="row-height" min="0" step="0.01" value="100"></div>
<div class="field"><label>Berat kotor (kg)</label><input type="number" class="row-gross" min="0" step="0.01" value="120"></div>
<div class="field field-actions"><button type="button" class="button button-secondary row-remove" hidden>Hapus</button><button type="button" class="button button-secondary" id="lcl-add">+ Baris</button></div>
</div></div>
<div class="form-actions"><button class="button button-primary" type="submit">Hitung biaya LCL</button></div>
</form>
<div class="cost-summary-body" id="lcl-result" hidden><div class="stats-grid">
<div class="stat-card"><p>Total CBM</p><strong id="r-cbm">—</strong></div>
<div class="stat-card"><p>Berat kotor total</p><strong id="r-gross">—</strong></div>
<div class="stat-card"><p>Basis tagihan (W/M)</p><strong id="r-basis">—</strong></div>
<div class="stat-card"><p>Tarif per m³</p><strong id="r-rate">—</strong></div>
<div class="stat-card"><p>Estimasi biaya</p><strong id="r-total">—</strong></div>
</div></div></section>
<script>
(function(){
    const form = document.getElementById('lcl-form');
    const result = document.getElementById('lcl-result');
    const rows = document.getElementById('lcl-rows');
    function params() {
        const p = new URLSearchParams();
        const rate = document.getElementById('rate_per_cbm').value.trim();
        if (rate) p.set('rate_per_cbm', rate);
        rows.querySelectorAll('.calculator-row').forEach((row, i) => {
            p.set('packages['+i+'][qty]', row.querySelector('.row-qty').value);
            p.set('packages['+i+'][length]', row.querySelector('.row-length').value);
            p.set('packages['+i+'][width]', row.querySelector('.row-width').value);
            p.set('packages['+i+'][height]', row.querySelector('.row-height').value);
            p.set('packages['+i+'][gross_weight]', row.querySelector('.row-gross').value);
        });
        return p;
    }
    document.getElementById('lcl-add').addEventListener('click', () => {
        const template = rows.querySelector('.calculator-row');
        const clone = template.cloneNode(true);
        rows.appendChild(clone);
        rows.querySelectorAll('.calculator-row').forEach((row) => row.querySelector('.row-remove').hidden = rows.querySelectorAll('.calculator-row').length === 1);
    });
    rows.addEventListener('click', (e) => {
        if (!e.target.classList.contains('row-remove')) return;
        e.target.closest('.calculator-row').remove();
        const remaining = rows.querySelectorAll('.calculator-row');
        remaining.forEach((row) => row.querySelector('.row-remove').hidden = remaining.length === 1);
    });
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        fetch('/api/calculators/lcl?'+params(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => {
                result.hidden = !ok;
                if (!ok) { alert('Periksa kembali input.'); return; }
                const idr = new Intl.NumberFormat('id-ID');
                document.getElementById('r-cbm').textContent = d.total_cbm.toLocaleString('id-ID', { maximumFractionDigits: 4 }) + ' m³';
                document.getElementById('r-gross').textContent = d.total_gross_weight.toLocaleString('id-ID') + ' kg';
                document.getElementById('r-basis').textContent = d.chargeable_basis.toLocaleString('id-ID', { maximumFractionDigits: 4 }) + (d.basis_note ? ' ('+d.basis_note+')' : '');
                document.getElementById('r-rate').textContent = 'Rp ' + idr.format(d.rate_per_cbm);
                document.getElementById('r-total').textContent = 'Rp ' + idr.format(d.total_cost);
            });
    });
})();
</script>
@endsection
