<div class="contact-row-inner">
<div class="field"><label for="contacts_{{ $i }}_type">Tipe</label><select id="contacts_{{ $i }}_type" name="contacts[{{ $i }}][type]"><option value="shipper" @selected(($contact['type'] ?? '')==='shipper')>Shipper (Pengirim)</option><option value="consignee" @selected(($contact['type'] ?? '')==='consignee')>Consignee (Penerima)</option></select></div>
<div class="field"><label for="contacts_{{ $i }}_name">Nama</label><input id="contacts_{{ $i }}_name" name="contacts[{{ $i }}][name]" value="{{ $contact['name'] ?? '' }}" placeholder="Nama shipper / consignee" required></div>
<div class="field"><label for="contacts_{{ $i }}_company">Perusahaan</label><input id="contacts_{{ $i }}_company" name="contacts[{{ $i }}][company]" value="{{ $contact['company'] ?? '' }}"></div>
<div class="field"><label for="contacts_{{ $i }}_email">Email</label><input id="contacts_{{ $i }}_email" type="email" name="contacts[{{ $i }}][email]" value="{{ $contact['email'] ?? '' }}"></div>
<div class="field"><label for="contacts_{{ $i }}_phone">Telepon</label><input id="contacts_{{ $i }}_phone" name="contacts[{{ $i }}][phone]" value="{{ $contact['phone'] ?? '' }}"></div>
<div class="field span-2"><label for="contacts_{{ $i }}_address">Alamat</label><textarea id="contacts_{{ $i }}_address" name="contacts[{{ $i }}][address]" rows="2">{{ $contact['address'] ?? '' }}</textarea></div>
<button type="button" class="button button-icon-danger" data-contacts-remove aria-label="Hapus baris">×</button>
</div>