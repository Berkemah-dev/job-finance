<div class="item-editor" data-quotation-item>
<div class="item-editor-head"><strong class="item-title">Item {{ is_numeric($index)?$index+1:'' }}</strong><button class="button button-danger" type="button" data-remove-item>Hapus item</button></div>
<div class="item-fields">
<div class="field description-field"><label for="item-{{ $index }}-description">Uraian biaya <span class="required">*</span></label><input id="item-{{ $index }}-description" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" maxlength="255" required placeholder="Contoh: Pengurusan dokumen"></div>
<div class="field"><label for="item-{{ $index }}-type">Jenis biaya</label><select id="item-{{ $index }}-type" name="items[{{ $index }}][type]" data-cost-type><option value="provision" @selected(($item['type'] ?? '')==='provision')>Provision</option><option value="temporary" @selected(($item['type'] ?? '')==='temporary')>Temporary</option></select></div>
<div class="field"><label for="item-{{ $index }}-unit">Satuan</label><input id="item-{{ $index }}-unit" name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? 'Layanan' }}" maxlength="30" required placeholder="Layanan"></div>
<div class="field"><label for="item-{{ $index }}-quantity">Jumlah</label><input id="item-{{ $index }}-quantity" type="number" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? '1' }}" min="0.01" max="999999.99" step="0.01" required data-quantity></div>
<div class="field"><label for="item-{{ $index }}-unit_cost">Modal / unit (Rp)</label><input id="item-{{ $index }}-unit_cost" type="number" name="items[{{ $index }}][unit_cost]" value="{{ $item['unit_cost'] ?? '0' }}" min="0" max="999999999.99" step="0.01" required data-unit-cost></div>
<div class="field"><label for="item-{{ $index }}-unit_price">Nilai jual / unit (Rp)</label><input id="item-{{ $index }}-unit_price" type="number" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? '0' }}" min="0" max="999999999.99" step="0.01" required data-unit-price></div>
</div><p class="form-help">Temporary ditagihkan sebesar modal; profit hanya berasal dari provision.</p>
</div>
