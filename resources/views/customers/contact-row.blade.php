<div class="contact-row" data-contact-row>
<div class="contact-row-inner">
<div class="field"><label for="contacts_{{ $i }}_type">Tipe</label><select id="contacts_{{ $i }}_type" name="contacts[{{ $i }}][type]"><option value="shipper" @selected(($contact['type'] ?? '')==='shipper')>Shipper (Pengirim)</option><option value="consignee" @selected(($contact['type'] ?? '')==='consignee')>Consignee (Penerima)</option></select></div>
<div class="field"><label for="contacts_{{ $i }}_name">Nama / Perusahaan <span class="required">*</span></label><input id="contacts_{{ $i }}_name" name="contacts[{{ $i }}][name]" value="{{ $contact['name'] ?? '' }}" placeholder="Nama shipper / consignee" required></div>
<div class="field"><label for="contacts_{{ $i }}_company">Perusahaan</label><input id="contacts_{{ $i }}_company" name="contacts[{{ $i }}][company]" value="{{ $contact['company'] ?? '' }}"></div>
<div class="field"><label for="contacts_{{ $i }}_email">Email</label><input id="contacts_{{ $i }}_email" type="email" name="contacts[{{ $i }}][email]" value="{{ $contact['email'] ?? '' }}"></div>
<div class="field"><label for="contacts_{{ $i }}_phone">Telepon</label><input id="contacts_{{ $i }}_phone" name="contacts[{{ $i }}][phone]" value="{{ $contact['phone'] ?? '' }}"></div>
<div class="field"><label for="contacts_{{ $i }}_country">Negara</label><input id="contacts_{{ $i }}_country" name="contacts[{{ $i }}][country]" value="{{ $contact['country'] ?? '' }}" placeholder="cth: Indonesia"></div>
<div style="display:flex;align-items:center;gap:8px;padding-top:22px;"><input id="contacts_{{ $i }}_is_active" type="checkbox" name="contacts[{{ $i }}][is_active]" value="1" style="width:16px;height:16px;flex-shrink:0;" @checked(filter_var($contact['is_active'] ?? true, FILTER_VALIDATE_BOOL))><label for="contacts_{{ $i }}_is_active" style="font-size:12px;font-weight:500;margin:0;cursor:pointer;">Aktif</label></div>
<div class="field span-2"><label for="contacts_{{ $i }}_address">Alamat</label><textarea id="contacts_{{ $i }}_address" name="contacts[{{ $i }}][address]" rows="2">{{ $contact['address'] ?? '' }}</textarea></div>
<div class="field span-2"><label for="contacts_{{ $i }}_notes">Catatan</label><textarea id="contacts_{{ $i }}_notes" name="contacts[{{ $i }}][notes]" rows="2" maxlength="2000">{{ $contact['notes'] ?? '' }}</textarea></div>
<button type="button" class="button button-icon-danger" data-contacts-remove aria-label="Hapus baris">×</button>
</div>
</div>