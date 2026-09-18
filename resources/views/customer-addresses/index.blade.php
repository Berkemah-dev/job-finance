@extends('layouts.app')
@section('title', 'Master Alamat Customer')
@section('content')
<x-menu-banner
    tag="SALES & CUSTOMER"
    title="Master Alamat Pengiriman"
    description="Kelola master lokasi dan alamat tujuan pengiriman customer untuk Surat Jalan dan dokumen operasional."
    icon="map-pin"
    art-title="Alamat logistik,"
    art-subtitle="tepat sasaran."
>
    <button type="button" class="button button-white" onclick="document.getElementById('modal-add-address').showModal();" style="display: inline-flex; align-items: center; gap: 8px;">
        <x-icon name="plus"/>
        <span>Tambah Alamat</span>
    </button>
</x-menu-banner>

<section class="panel">
    <form class="filter-bar" method="GET" action="{{ route('customer-addresses.index') }}">
        <input name="search" value="{{ $search }}" placeholder="Cari nama lokasi, alamat, atau customer" aria-label="Cari alamat">
        <select name="customer_id" aria-label="Filter customer">
            <option value="">Semua Customer</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" @selected($customerId === $c->id)>{{ $c->name }} ({{ $c->code }})</option>
            @endforeach
        </select>
        <button class="button button-primary">Cari</button>
        <a class="text-link" href="{{ route('customer-addresses.index') }}">Reset</a>
        <a class="button button-secondary button-sm" href="{{ route('customers.index') }}" style="margin-left: auto;">
            ← Kembali ke Customer
        </a>
    </form>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Nama Lokasi</th>
                    <th>Alamat Lengkap</th>
                    <th>PIC / Telepon</th>
                    <th>Default</th>
                    <th>Status</th>
                    @can('customers.manage')
                        <th style="text-align: center;">Aksi</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @forelse($addresses as $addr)
                    <tr>
                        <td>
                            <strong>{{ $addr->customer?->name ?? '—' }}</strong>
                            @if($addr->customer?->code)
                                <br><small class="muted-cell">{{ $addr->customer->code }}</small>
                            @endif
                        </td>
                        <td>
                            <strong style="color: #1e293b;">{{ $addr->location_name }}</strong>
                        </td>
                        <td style="max-width: 320px; white-space: normal; line-height: 1.4;">
                            {{ $addr->address }}
                        </td>
                        <td>
                            {{ $addr->pic_name ?: '—' }}
                            @if($addr->pic_phone)
                                <br><small class="muted-cell">{{ $addr->pic_phone }}</small>
                            @endif
                        </td>
                        <td>
                            @if($addr->is_default)
                                <span class="status-badge status-active">Utama</span>
                            @else
                                <span class="muted-cell">—</span>
                            @endif
                        </td>
                        <td>
                            @if($addr->is_active)
                                <span class="status-badge status-active">Aktif</span>
                            @else
                                <span class="status-badge status-inactive">Nonaktif</span>
                            @endif
                        </td>
                        @can('customers.manage')
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 6px; justify-content: center;">
                                    <button type="button" class="button button-secondary button-sm" onclick="openEditAddressModal({{ json_encode($addr) }})">Edit</button>
                                    <form method="POST" action="{{ route('customer-addresses.destroy', $addr) }}" data-confirm="Hapus alamat lokasi {{ $addr->location_name }}?" style="display: inline;">
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
                        <td colspan="{{ auth()->user()->can('customers.manage') ? 7 : 6 }}">
                            <div class="empty-state">
                                <h3>Belum ada master alamat</h3>
                                <p>Tambahkan lokasi dan alamat gudang/tujuan pengiriman untuk customer.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $addresses->links() }}</div>
</section>

@can('customers.manage')
    {{-- Modal Tambah Alamat --}}
    <dialog id="modal-add-address" class="modal-dialog">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #eff6ff; color: #2563eb; font-size: 20px; box-shadow: 0 2px 6px rgba(37,99,235,0.15);">
                    📍
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15.5px; font-weight: 700; color: #0f172a;">Tambah Alamat Pengiriman Baru</h3>
                    <p style="margin: 2px 0 0; font-size: 11.5px; color: #64748b;">Lokasi gudang/depo customer untuk Surat Jalan.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-address').close()" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: grid; place-items: center; cursor: pointer; font-size: 14px; transition: all .15s ease;" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';" onmouseout="this.style.background='#fff';this.style.color='#64748b';">✕</button>
        </div>
        <form method="POST" action="{{ route('customer-addresses.store') }}" style="padding: 22px 24px;">
            @csrf
            <div style="display: grid; gap: 16px;">
                <div class="field">
                    <label for="add_customer_id" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Customer <span style="color: #ef4444;">*</span></label>
                    <select id="add_customer_id" name="customer_id" required>
                        <option value="">Pilih Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected($customerId === $c->id)>{{ $c->name }} ({{ $c->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="add_location_name" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nama Lokasi / Gudang / Label <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="add_location_name" name="location_name" placeholder="contoh: Gudang Cikarang Barat / Pabrik Karawang" required maxlength="160">
                </div>
                <div class="field">
                    <label for="add_address" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Alamat Lengkap <span style="color: #ef4444;">*</span></label>
                    <textarea id="add_address" name="address" rows="3" placeholder="Jalan, Kawasan Industri, Blok/No, Kota, Kode Pos" required maxlength="5000"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="field">
                        <label for="add_pic_name" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nama PIC Lokasi</label>
                        <input type="text" id="add_pic_name" name="pic_name" placeholder="Nama penerima di lokasi" maxlength="160">
                    </div>
                    <div class="field">
                        <label for="add_pic_phone" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Telepon / HP PIC</label>
                        <input type="text" id="add_pic_phone" name="pic_phone" placeholder="08..." maxlength="50">
                    </div>
                </div>
                <div class="field" style="display: flex; align-items: center; gap: 8px; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <input type="checkbox" id="add_is_default" name="is_default" value="1" style="width: 16px; height: 16px;">
                    <label for="add_is_default" style="font-size: 12.5px; margin: 0; font-weight: 600; color: #334155; cursor: pointer;">Jadikan Alamat Utama untuk Customer ini</label>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('modal-add-address').close()">Batal</button>
                <button type="submit" class="button button-primary">
                    <x-icon name="check"/> Simpan Alamat
                </button>
            </div>
        </form>
    </dialog>

    {{-- Modal Edit Alamat --}}
    <dialog id="modal-edit-address" class="modal-dialog">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #eff6ff; color: #2563eb; font-size: 20px; box-shadow: 0 2px 6px rgba(37,99,235,0.15);">
                    📍
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15.5px; font-weight: 700; color: #0f172a;">Edit Alamat Pengiriman</h3>
                    <p style="margin: 2px 0 0; font-size: 11.5px; color: #64748b;">Perbarui lokasi gudang atau kontak PIC penerima.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-edit-address').close()" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: grid; place-items: center; cursor: pointer; font-size: 14px; transition: all .15s ease;" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';" onmouseout="this.style.background='#fff';this.style.color='#64748b';">✕</button>
        </div>
        <form id="form-edit-address" method="POST" action="" style="padding: 22px 24px;">
            @csrf
            @method('PUT')
            <div style="display: grid; gap: 16px;">
                <div class="field">
                    <label for="edit_location_name" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nama Lokasi / Label <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="edit_location_name" name="location_name" required maxlength="160">
                </div>
                <div class="field">
                    <label for="edit_address" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Alamat Lengkap <span style="color: #ef4444;">*</span></label>
                    <textarea id="edit_address" name="address" rows="3" required maxlength="5000"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="field">
                        <label for="edit_pic_name" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nama PIC Lokasi</label>
                        <input type="text" id="edit_pic_name" name="pic_name" maxlength="160">
                    </div>
                    <div class="field">
                        <label for="edit_pic_phone" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Telepon / HP PIC</label>
                        <input type="text" id="edit_pic_phone" name="pic_phone" maxlength="50">
                    </div>
                </div>
                <div style="display: flex; gap: 16px; align-items: center; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div class="field" style="display: flex; align-items: center; gap: 8px; margin: 0;">
                        <input type="checkbox" id="edit_is_default" name="is_default" value="1" style="width: 16px; height: 16px;">
                        <label for="edit_is_default" style="font-size: 12.5px; margin: 0; font-weight: 600; color: #334155; cursor: pointer;">Alamat Utama</label>
                    </div>
                    <div class="field" style="display: flex; align-items: center; gap: 8px; margin: 0;">
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1" style="width: 16px; height: 16px;">
                        <label for="edit_is_active" style="font-size: 12.5px; margin: 0; font-weight: 600; color: #334155; cursor: pointer;">Status Aktif</label>
                    </div>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('modal-edit-address').close()">Batal</button>
                <button type="submit" class="button button-primary">
                    <x-icon name="check"/> Simpan Perubahan
                </button>
            </div>
        </form>
    </dialog>

    <script>
        function openEditAddressModal(addr) {
            const form = document.getElementById('form-edit-address');
            form.action = '{{ url("customer-addresses") }}/' + addr.id;
            document.getElementById('edit_location_name').value = addr.location_name;
            document.getElementById('edit_address').value = addr.address;
            document.getElementById('edit_pic_name').value = addr.pic_name || '';
            document.getElementById('edit_pic_phone').value = addr.pic_phone || '';
            document.getElementById('edit_is_default').checked = addr.is_default;
            document.getElementById('edit_is_active').checked = addr.is_active;
            document.getElementById('modal-edit-address').showModal();
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
@endsection
