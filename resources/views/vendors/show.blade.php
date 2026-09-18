@extends('layouts.app')
@section('title','Detail Vendor')
@section('content')
<div class="page-heading"><div><p class="eyebrow">MASTER DATA</p><h1>{{ $vendor->code }}</h1><p>{{ $vendor->name }}</p></div><a class="button button-secondary" href="{{ route('vendors.index') }}">← Kembali</a></div>
@if($vendor->trashed())<div class="info-note"><strong>Vendor nonaktif.</strong> Tidak dapat dipilih untuk transaksi baru. <form method="POST" action="{{ route('vendors.restore',$vendor) }}" data-confirm="Aktifkan kembali vendor ini?" class="inline-form">@csrf<input type="hidden" name="lock_version" value="{{ $vendor->lock_version }}"><button class="text-link">Aktifkan kembali</button></form></div>@endif
<section class="panel"><div class="panel-heading"><h2>Informasi vendor</h2><div class="action-group">@if($vendor->trashed())<span class="status-badge status-inactive">Diarsipkan</span>@elseif($vendor->is_active)<span class="status-badge status-active">Aktif</span>@else<span class="status-badge status-inactive">Nonaktif</span>@endif<a class="button button-secondary button-sm" href="{{ route('vendors.edit',$vendor) }}">Edit</a>@if(!$vendor->trashed())<form method="POST" action="{{ route('vendors.toggle',$vendor) }}" data-confirm="{{ $vendor->is_active?'Nonaktifkan':'Aktifkan' }} vendor ini?">@csrf<input type="hidden" name="lock_version" value="{{ $vendor->lock_version }}"><button class="button button-{{ $vendor->is_active?'secondary':'primary' }} button-sm">{{ $vendor->is_active?'Nonaktifkan':'Aktifkan' }}</button></form>@endif</div></div>
<dl class="detail-grid">
    <div><dt>Kode vendor</dt><dd>{{ $vendor->code }}</dd></div>
    <div><dt>Kategori</dt><dd>@foreach($vendor->categoryLabels() as $label)<span class="status-badge">{{ $label }}</span> @endforeach</dd></div>
    <div><dt>PIC</dt><dd>{{ $vendor->pic ?? '—' }}</dd></div>
    <div><dt>Email</dt><dd>{{ $vendor->email ?? '—' }}</dd></div>
    <div><dt>Telepon</dt><dd>{{ $vendor->phone ?? '—' }}</dd></div>
    <div><dt>NPWP / TAX ID</dt><dd>{{ $vendor->tax_number ?? '—' }}</dd></div>
    <div><dt>Nama Bank</dt><dd>{{ $vendor->bank_name ?? '—' }}</dd></div>
    <div><dt>No. Rekening</dt><dd><strong>{{ $vendor->bank_account_number ?? '—' }}</strong></dd></div>
    <div><dt>Nama Rekening</dt><dd>{{ $vendor->bank_account_name ?? '—' }}</dd></div>
    <div><dt>Negara</dt><dd>{{ $vendor->country ?? '—' }}</dd></div>
    <div class="span-2"><dt>Alamat</dt><dd>{{ $vendor->address ?? '—' }}</dd></div>
    <div class="span-2"><dt>Catatan</dt><dd>{{ $vendor->notes ?? '—' }}</dd></div>
</dl></section>

@if($vendor->isTrucking() || $vendor->trucks->isNotEmpty())
<section class="panel" style="margin-top: 24px;">
    <div class="panel-heading">
        <div>
            <h2>Armada & Supir Trucking</h2>
            <p style="margin: 2px 0 0; color: #64748b; font-size: 12.5px;">Data supir, nomor telepon, dan nomor plat truk yang terhubung ke Surat Jalan.</p>
        </div>
        @can('vendors.manage')
            <button type="button" class="button button-primary button-sm" onclick="document.getElementById('modal-add-truck').showModal(); window.initCustomSelects?.();">+ Tambah Supir / Armada</button>
        @endcan
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Plat Nomor Truk</th>
                    <th>Nama Supir</th>
                    <th>No. Telepon / HP Supir</th>
                    <th>Jenis Kendaraan</th>
                    <th>Catatan</th>
                    <th>Status</th>
                    @can('vendors.manage')
                        <th style="text-align: center;">Aksi</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @forelse($vendor->trucks as $truck)
                    <tr>
                        <td><strong>{{ $truck->plate_number }}</strong></td>
                        <td>{{ $truck->driver_name }}</td>
                        <td>{{ $truck->driver_phone ?: '—' }}</td>
                        <td>{{ $truck->vehicle_type ?: '—' }}</td>
                        <td>{{ $truck->notes ?: '—' }}</td>
                        <td>
                            @if($truck->is_active)
                                <span class="status-badge status-active">Aktif</span>
                            @else
                                <span class="status-badge status-inactive">Nonaktif</span>
                            @endif
                        </td>
                        @can('vendors.manage')
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 6px; justify-content: center;">
                                    <button type="button" class="button button-secondary button-sm" onclick="openEditTruckModal({{ json_encode($truck) }})">Edit</button>
                                    <form method="POST" action="{{ route('vendors.trucks.destroy', [$vendor, $truck]) }}" data-confirm="Hapus armada {{ $truck->plate_number }} ({{ $truck->driver_name }})?" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="button button-secondary button-sm" style="color: #dc2626;">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->can('vendors.manage') ? 7 : 6 }}">
                            <div class="empty-state">
                                <h3>Belum ada armada & supir</h3>
                                <p>Tambahkan supir dan plat nomor truk agar dapat dipilih pada Surat Jalan.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@can('vendors.manage')
    {{-- Modal Tambah Armada --}}
    <dialog id="modal-add-truck" class="modal-dialog">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #eff6ff; color: #2563eb; font-size: 20px; box-shadow: 0 2px 6px rgba(37,99,235,0.15);">
                    🚚
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15.5px; font-weight: 700; color: #0f172a;">Tambah Supir & Armada Trucking</h3>
                    <p style="margin: 2px 0 0; font-size: 11.5px; color: #64748b;">Terhubung otomatis ke Surat Jalan & operasional pengiriman.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-truck').close()" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: grid; place-items: center; cursor: pointer; font-size: 14px; transition: all .15s ease;" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';" onmouseout="this.style.background='#fff';this.style.color='#64748b';">✕</button>
        </div>
        <form method="POST" action="{{ route('vendors.trucks.store', $vendor) }}" style="padding: 22px 24px;">
            @csrf
            <div style="display: grid; gap: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="field">
                        <label for="new_plate_number" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Plat Nomor Truk <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="new_plate_number" name="plate_number" placeholder="contoh: B 9123 UE" required maxlength="30" style="text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                    </div>
                    <div class="field">
                        <label for="new_vehicle_type" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Jenis Kendaraan</label>
                        <select id="new_vehicle_type" name="vehicle_type" data-custom-select placeholder="Pilih atau ketik jenis...">
                            <option value="">Pilih atau ketik jenis...</option>
                            <option value="Trailer 40ft">Trailer 40ft</option>
                            <option value="Trailer 20ft">Trailer 20ft</option>
                            <option value="Tronton Wingbox">Tronton Wingbox</option>
                            <option value="Colt Diesel Double (CDD)">Colt Diesel Double (CDD)</option>
                            <option value="Colt Diesel Engkel (CDE)">Colt Diesel Engkel (CDE)</option>
                            <option value="Blind Van">Blind Van</option>
                            <option value="Engkel Box">Engkel Box</option>
                            <option value="Fuso Box">Fuso Box</option>
                            <option value="Truk Gandeng">Truk Gandeng</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="field">
                        <label for="new_driver_name" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nama Supir <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="new_driver_name" name="driver_name" placeholder="contoh: Bambang Supriyadi" required maxlength="160">
                    </div>
                    <div class="field">
                        <label for="new_driver_phone" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nomor Telepon / HP Supir</label>
                        <input type="text" id="new_driver_phone" name="driver_phone" placeholder="contoh: 0812-3456-7890" maxlength="50">
                    </div>
                </div>
                <div class="field">
                    <label for="new_notes" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Catatan Tambahan (Opsional)</label>
                    <input type="text" id="new_notes" name="notes" placeholder="contoh: Armada utama rute Priok - Cikarang" maxlength="255">
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('modal-add-truck').close()">Batal</button>
                <button type="submit" class="button button-primary">
                    <x-icon name="check"/> Simpan Armada
                </button>
            </div>
        </form>
    </dialog>

    {{-- Modal Edit Armada --}}
    <dialog id="modal-edit-truck" class="modal-dialog">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #eff6ff; color: #2563eb; font-size: 20px; box-shadow: 0 2px 6px rgba(37,99,235,0.15);">
                    🚚
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15.5px; font-weight: 700; color: #0f172a;">Edit Supir & Armada Trucking</h3>
                    <p style="margin: 2px 0 0; font-size: 11.5px; color: #64748b;">Perbarui informasi armada atau data kontak supir.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-edit-truck').close()" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: grid; place-items: center; cursor: pointer; font-size: 14px; transition: all .15s ease;" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';" onmouseout="this.style.background='#fff';this.style.color='#64748b';">✕</button>
        </div>
        <form id="form-edit-truck" method="POST" action="" style="padding: 22px 24px;">
            @csrf
            @method('PUT')
            <div style="display: grid; gap: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="field">
                        <label for="edit_plate_number" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Plat Nomor Truk <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="edit_plate_number" name="plate_number" required maxlength="30" style="text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                    </div>
                    <div class="field">
                        <label for="edit_vehicle_type" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Jenis Kendaraan</label>
                        <select id="edit_vehicle_type" name="vehicle_type" data-custom-select placeholder="Pilih atau ketik jenis...">
                            <option value="">Pilih atau ketik jenis...</option>
                            <option value="Trailer 40ft">Trailer 40ft</option>
                            <option value="Trailer 20ft">Trailer 20ft</option>
                            <option value="Tronton Wingbox">Tronton Wingbox</option>
                            <option value="Colt Diesel Double (CDD)">Colt Diesel Double (CDD)</option>
                            <option value="Colt Diesel Engkel (CDE)">Colt Diesel Engkel (CDE)</option>
                            <option value="Blind Van">Blind Van</option>
                            <option value="Engkel Box">Engkel Box</option>
                            <option value="Fuso Box">Fuso Box</option>
                            <option value="Truk Gandeng">Truk Gandeng</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="field">
                        <label for="edit_driver_name" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nama Supir <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="edit_driver_name" name="driver_name" required maxlength="160">
                    </div>
                    <div class="field">
                        <label for="edit_driver_phone" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nomor Telepon / HP Supir</label>
                        <input type="text" id="edit_driver_phone" name="driver_phone" maxlength="50">
                    </div>
                </div>
                <div class="field">
                    <label for="edit_notes" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Catatan Tambahan (Opsional)</label>
                    <input type="text" id="edit_notes" name="notes" maxlength="255">
                </div>
                <div class="field" style="display: flex; align-items: center; gap: 8px; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <input type="checkbox" id="edit_is_active" name="is_active" value="1" style="width: 16px; height: 16px;">
                    <label for="edit_is_active" style="font-size: 12.5px; margin: 0; font-weight: 600; color: #334155; cursor: pointer;">Status Aktif (dapat dipilih di Surat Jalan)</label>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('modal-edit-truck').close()">Batal</button>
                <button type="submit" class="button button-primary">
                    <x-icon name="check"/> Simpan Perubahan
                </button>
            </div>
        </form>
    </dialog>

    <script>
        function openEditTruckModal(truck) {
            const form = document.getElementById('form-edit-truck');
            form.action = '{{ url("vendors/{$vendor->id}/trucks") }}/' + truck.id;
            document.getElementById('edit_plate_number').value = truck.plate_number;
            document.getElementById('edit_driver_name').value = truck.driver_name;
            document.getElementById('edit_driver_phone').value = truck.driver_phone || '';
            
            const vehicleSelect = document.getElementById('edit_vehicle_type');
            if (vehicleSelect) {
                if (truck.vehicle_type && !Array.from(vehicleSelect.options).some(o => o.value === truck.vehicle_type)) {
                    const opt = document.createElement('option');
                    opt.value = truck.vehicle_type;
                    opt.textContent = truck.vehicle_type;
                    vehicleSelect.appendChild(opt);
                }
                vehicleSelect.value = truck.vehicle_type || '';
                vehicleSelect.dispatchEvent(new Event('change'));
            }

            document.getElementById('edit_notes').value = truck.notes || '';
            document.getElementById('edit_is_active').checked = truck.is_active;
            document.getElementById('modal-edit-truck').showModal();
            window.initCustomSelects?.();
        }

        // Tutup modal ketika mengklik backdrop luar
        document.querySelectorAll('dialog.modal-dialog').forEach(dialog => {
            dialog.addEventListener('click', (e) => {
                if (e.target.closest('.custom-select-dropdown') || e.target.closest('.custom-select-wrapper')) {
                    return;
                }
                const rect = dialog.getBoundingClientRect();
                const isInDialog = (rect.top <= e.clientY && e.clientY <= rect.top + rect.height &&
                    rect.left <= e.clientX && e.clientX <= rect.left + rect.width);
                if (!isInDialog) {
                    dialog.close();
                }
            });
        });
    </script>
@endcan
@endif

<section class="panel" style="margin-top: 24px;"><div class="panel-heading"><h2>Tarif trucking</h2><a class="text-link" href="{{ route('pricing.trucking.index') }}">Lihat tarif</a></div>
<div class="table-scroll"><table><thead><tr><th>Rute</th><th>Kontainer</th><th>Overweight</th><th class="money">Tarif</th><th>Berlaku</th><th>Status</th></tr></thead><tbody>@forelse($vendor->truckingPrices as $price)<tr><td>{{ $price->port_origin }} → {{ $price->destination }}</td><td>{{ strtoupper($price->container_type) }}</td><td>{{ $price->overweight?'Ya':'—' }}</td><td class="money">{{ $price->currency }} {{ number_format((float)$price->price, 0, ',', '.') }}</td><td>{{ $price->effective_date->format('d/m/Y') }}@if($price->effective_until)<br><small class="muted-cell">s.d. {{ $price->effective_until->format('d/m/Y') }}</small>@endif</td><td>@if($price->is_active)<span class="status-badge status-active">Aktif</span>@else<span class="status-badge status-inactive">Nonaktif</span>@endif</td></tr>@empty<tr><td colspan="6"><div class="empty-state"><h3>Belum ada tarif trucking</h3><p>Tambahkan tarif pada menu Tarif Trucking.</p></div></td></tr>@endforelse</tbody></table></div></section>
@endsection