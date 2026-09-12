<div class="item-editor" data-quotation-item>
<div class="item-editor-head"><strong class="item-title">Item {{ is_numeric($index)?$index+1:'' }}</strong><button class="button button-danger" type="button" data-remove-item>Hapus item</button></div>
<div class="item-fields">
<div class="field description-field"><label for="item-{{ $index }}-description">Uraian pembayaran <span class="required">*</span></label><select id="item-{{ $index }}-description" name="items[{{ $index }}][description]" required><option value="">Pilih uraian biaya</option>@foreach($charges ?? [] as $charge)<option value="{{ $charge->name }}" @selected(($item['description'] ?? '')===$charge->name)>{{ $charge->name }}</option>@endforeach</select></div>
<div class="field note-field" style="grid-column: 1 / -1;"><label for="item-{{ $index }}-note">Catatan biaya / Note</label><input id="item-{{ $index }}-note" name="items[{{ $index }}][note]" value="{{ $item['note'] ?? '' }}" maxlength="255" placeholder="Contoh: Exclude demurrage / at cost / free time 7 days"></div>
<input type="hidden" name="items[{{ $index }}][type]" value="provision">
<div class="field"><label for="item-{{ $index }}-unit">Satuan</label><select id="item-{{ $index }}-unit" name="items[{{ $index }}][unit]" required><option value="">Pilih satuan</option>@foreach($units ?? [] as $unit)<option value="{{ $unit->name }}" @selected(($item['unit'] ?? 'Shipment')===$unit->name)>{{ $unit->name }}</option>@endforeach</select></div>
<div class="field"><label for="item-{{ $index }}-quantity">Jumlah</label><input id="item-{{ $index }}-quantity" type="number" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? '1' }}" min="0.01" max="999999.99" step="0.01" required data-quantity></div>
<div class="field"><label for="item-{{ $index }}-unit_cost">Modal / unit (IDR)</label><input id="item-{{ $index }}-unit_cost" type="number" name="items[{{ $index }}][unit_cost]" value="{{ $item['unit_cost'] ?? '0' }}" min="0" max="999999999.99" step="0.01" required data-unit-cost></div>
<div class="field"><label for="item-{{ $index }}-unit_price">Nilai jual / unit (IDR)</label><input id="item-{{ $index }}-unit_price" type="number" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? '0' }}" min="0" max="999999999.99" step="0.01" required data-unit-price></div>
</div>
<div class="item-pricing" data-pricing-block>
<div class="field"><label for="item-{{ $index }}-pricing-source">Sumber tarif</label><select id="item-{{ $index }}-pricing-source" data-pricing-source><option value="manual" @selected(($item['pricing_source'] ?? 'manual')==='manual')>Manual</option><option value="trucking" @selected(($item['pricing_source'] ?? '')==='trucking')>Tarif trucking</option></select></div>
<div class="field"><label for="item-{{ $index }}-container_type">Kontainer</label><select id="item-{{ $index }}-container_type" name="items[{{ $index }}][container_type]" data-container><option value="">—</option>@foreach($containerUnits ?? \App\Models\ContainerUnit::options() as $key=>$label)<option value="{{ $key }}" @selected(($item['container_type'] ?? '')===$key)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="item-{{ $index }}-overweight"><input id="item-{{ $index }}-overweight" type="checkbox" name="items[{{ $index }}][overweight]" value="1" data-overweight @checked(($item['overweight'] ?? false))> Overweight</label></div>
<div class="field"><label for="item-{{ $index }}-gross_weight">Berat kotor (kg)</label><input id="item-{{ $index }}-gross_weight" type="number" name="items[{{ $index }}][gross_weight]" value="{{ $item['gross_weight'] ?? '' }}" min="0" max="999999.99" step="0.01" data-gross-weight></div>
<div class="field"><label for="item-{{ $index }}-volume">Volume (m³)</label><input id="item-{{ $index }}-volume" type="number" name="items[{{ $index }}][volume]" value="{{ $item['volume'] ?? '' }}" min="0" max="999999.99" step="0.001" data-volume></div>
<div class="field lookup-field" data-lookup-only><label for="item-{{ $index }}-origin">Pelabuhan asal</label><input id="item-{{ $index }}-origin" data-origin value="{{ $item['pricing_snapshot']['port_origin'] ?? '' }}" placeholder="cth: Tanjung Priok" data-lookup-only></div>
<div class="field lookup-field" data-lookup-only><label for="item-{{ $index }}-destination">Tujuan</label><input id="item-{{ $index }}-destination" data-destination value="{{ $item['pricing_snapshot']['destination'] ?? '' }}" placeholder="cth: Surabaya" data-lookup-only></div>
<div class="field lookup-field" data-lookup-only><label>&nbsp;</label><button class="button button-secondary button-sm" type="button" data-pricing-search>Tarik tarif</button></div>
<input type="hidden" name="items[{{ $index }}][pricing_source]" data-field-pricing-source value="{{ $item['pricing_source'] ?? 'manual' }}">
<input type="hidden" name="items[{{ $index }}][pricing_id]" data-field-pricing-id value="{{ $item['pricing_id'] ?? '' }}">
<input type="hidden" name="items[{{ $index }}][currency]" data-field-currency value="{{ $item['currency'] ?? 'IDR' }}">
<input type="hidden" name="items[{{ $index }}][exchange_rate]" data-field-exchange-rate value="{{ $item['exchange_rate'] ?? '1.00' }}">
<input type="hidden" name="items[{{ $index }}][pricing_snapshot]" data-field-snapshot value="{{ ($item['pricing_snapshot'] ?? null) ? json_encode($item['pricing_snapshot']) : '' }}">
</div>
<p class="pricing-status" data-pricing-status></p>
<p class="form-help">Temporary ditagihkan sebesar modal; profit hanya dari provision. Tarif trucking memakai kurs mingguan aktif saat quotation.</p>
</div>
