@extends('layouts.app')
@section('title','Detail Customer')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">SALES & CUSTOMER</p><h1>{{ $customer->code }}</h1><p>{{ $customer->name }}</p></div>
    <div class="action-group">
        <a class="button button-secondary" href="{{ route('customers.index') }}">← Kembali</a>
        @if(!$customer->trashed())<a class="button button-primary" href="{{ route('customers.edit',$customer) }}">Edit Customer</a>@endif
        @if(!$customer->trashed() && ($customer->approval_status ?? 'approved') === 'pending' && auth()->user()?->hasRole(['finance-manager','finance','super-admin','admin']))
            <form method="POST" action="{{ route('customers.approve',$customer) }}" data-confirm="Setujui customer ini?">
                @csrf
                <input type="hidden" name="lock_version" value="{{ $customer->lock_version }}">
                <button class="button button-primary">Approve Customer</button>
            </form>
        @endif
    </div>
</div>
@if(($customer->approval_status ?? 'approved') === 'pending')<div class="info-note"><strong>Customer menunggu approval Finance Manager.</strong> Customer belum bisa dipakai untuk quotation sampai disetujui.</div>@endif
@if($customer->trashed())<div class="info-note"><strong>Customer nonaktif.</strong> Tidak dapat dipilih untuk transaksi baru. <form method="POST" action="{{ route('customers.restore',$customer) }}" data-confirm="Aktifkan kembali customer ini?" class="inline-form">@csrf<input type="hidden" name="lock_version" value="{{ $customer->lock_version }}"><button class="text-link">Aktifkan kembali</button></form></div>@endif

<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading"><h2><x-icon name="user" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Informasi Customer</h2>@if($customer->trashed())<span class="status-badge status-inactive">Nonaktif</span>@elseif(($customer->approval_status ?? 'approved') === 'pending')<span class="status-badge status-draft">Menunggu approval</span>@else<span class="status-badge status-active">Aktif</span>@endif</div>
    <dl class="detail-grid"><div><dt>Kode customer</dt><dd>{{ $customer->code }}</dd></div><div><dt>Nama</dt><dd>{{ $customer->name }}</dd></div><div><dt>Kontak</dt><dd>{{ $customer->contact_name ?? '—' }}</dd></div><div><dt>Email</dt><dd>{{ $customer->email ?? '—' }}</dd></div><div><dt>Telepon</dt><dd>{{ $customer->phone ?? '—' }}</dd></div><div><dt>NPWP</dt><dd>{{ $customer->tax_number ?? '—' }}</dd></div><div><dt>Syarat pembayaran default</dt><dd>{{ config('operations.customer_payment_terms.'.$customer->default_payment_terms) ?? $customer->default_payment_terms ?? '—' }}</dd></div><div><dt>Alamat</dt><dd>{{ $customer->address ?? '—' }}</dd></div><div><dt>Dibuat</dt><dd>{{ $customer->created_at?->format('d/m/Y H:i') }} · Diperbarui {{ $customer->updated_at?->format('d/m/Y H:i') }}</dd></div><div><dt>Approval</dt><dd>@if(($customer->approval_status ?? 'approved') === 'pending')Menunggu Finance Manager@else Approved @if($customer->approver) oleh {{ $customer->approver->name }}@endif @if($customer->approved_at) · {{ $customer->approved_at->format('d/m/Y H:i') }}@endif @endif</dd></div></dl>
</section>

<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading"><h2><x-icon name="file" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Document Details</h2><span class="subtle">Informasi pencetakan dokumen operasional & pabean</span></div>
    <dl class="detail-grid">
        <div><dt>Nama Customer</dt><dd><strong>{{ $customer->name }}</strong></dd></div>
        <div><dt>Nama Pemberi Kuasa (DNP/SK)</dt><dd>{{ $customer->authorizer_name ?? '—' }}</dd></div>
        <div><dt>Jabatan Pemberi Kuasa (DNP/SK)</dt><dd>{{ $customer->authorizer_title ?? '—' }}</dd></div>
        <div class="span-2"><dt>Alamat Resmi Perusahaan (BL/AWB/SI/Booking/SK/DNP)</dt><dd>{{ $customer->address ?? '—' }}</dd></div>
    </dl>
</section>

{{-- ========================================================================= --}}
{{-- EXPORT – LIST CONSIGNEE                                                   --}}
{{-- ========================================================================= --}}
<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading">
        <div>
            <h2><x-icon name="send" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Export – List Consignee</h2>
            <span class="subtle">Daftar penerima barang (Consignee) luar negeri saat customer melakukan ekspor</span>
        </div>
        <div class="action-group">
            @can('customers.manage')
                <button type="button" class="button button-primary button-sm" onclick="document.getElementById('modal-add-consignee').showModal()">+ Tambah Consignee</button>
            @endcan
        </div>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Nama Consignee / Perusahaan</th>
                    <th>Negara</th>
                    <th>Alamat Lengkap</th>
                    <th>Kontak / PIC</th>
                    <th>Status</th>
                    @can('customers.manage')
                        <th style="text-align: right;">Aksi</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @php
                    $consignees = $customer->contacts->where('type', 'consignee');
                @endphp
                @forelse($consignees as $consignee)
                    <tr>
                        <td>
                            <strong>{{ $consignee->name }}</strong>
                            @if($consignee->company && $consignee->company !== $consignee->name)
                                <br><small class="muted-cell">{{ $consignee->company }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="status-badge" style="background:#e0e7ff; color:#3730a3; font-weight:600;">
                                {{ $consignee->country ?: '—' }}
                            </span>
                        </td>
                        <td style="max-width: 320px; white-space: normal; line-height: 1.4;">
                            {{ $consignee->address ?: '—' }}
                        </td>
                        <td>
                            @if($consignee->email)<div>{{ $consignee->email }}</div>@endif
                            @if($consignee->phone)<small class="muted-cell">{{ $consignee->phone }}</small>@endif
                            @if(!$consignee->email && !$consignee->phone)—@endif
                        </td>
                        <td>
                            @if($consignee->is_active)
                                <span class="status-badge status-active">Aktif</span>
                            @else
                                <span class="status-badge status-inactive">Nonaktif</span>
                            @endif
                        </td>
                        @can('customers.manage')
                            <td style="text-align: right;">
                                <div class="table-actions" style="justify-content: flex-end;">
                                    <a class="btn-action btn-action-primary" href="{{ route('customer-contacts.edit', $consignee) }}" title="Edit Consignee" data-tooltip="Edit">
                                        <x-icon name="edit"/>
                                    </a>
                                    <form method="POST" action="{{ route('customer-contacts.destroy', $consignee) }}" data-confirm="Hapus consignee {{ $consignee->name }}?" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="lock_version" value="{{ $consignee->lock_version }}">
                                        <input type="hidden" name="redirect_to" value="{{ route('customers.show', $customer) }}">
                                        <button type="submit" class="btn-action btn-action-danger" title="Hapus Consignee" data-tooltip="Hapus"><x-icon name="trash"/></button>
                                    </form>
                                </div>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->can('customers.manage') ? 6 : 5 }}">
                            <div class="empty-state">
                                <h3>Belum ada data Consignee</h3>
                                <p>Tambahkan daftar penerima luar negeri untuk digunakan otomatis saat pembuatan penawaran (Quotation) & dokumen ekspor.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- ========================================================================= --}}
{{-- IMPORT – LIST SHIPPER                                                     --}}
{{-- ========================================================================= --}}
<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading">
        <div>
            <h2><x-icon name="inbox" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Import – List Shipper</h2>
            <span class="subtle">Daftar pengirim barang / supplier (Shipper) luar negeri saat customer melakukan impor</span>
        </div>
        <div class="action-group">
            @can('customers.manage')
                <button type="button" class="button button-primary button-sm" onclick="document.getElementById('modal-add-shipper').showModal()">+ Tambah Shipper</button>
            @endcan
        </div>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Nama Shipper / Supplier</th>
                    <th>Negara Asal</th>
                    <th>Alamat Lengkap</th>
                    <th>Kontak / PIC</th>
                    <th>Status</th>
                    @can('customers.manage')
                        <th style="text-align: right;">Aksi</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @php
                    $shippers = $customer->contacts->where('type', 'shipper');
                @endphp
                @forelse($shippers as $shipper)
                    <tr>
                        <td>
                            <strong>{{ $shipper->name }}</strong>
                            @if($shipper->company && $shipper->company !== $shipper->name)
                                <br><small class="muted-cell">{{ $shipper->company }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="status-badge" style="background:#fef3c7; color:#92400e; font-weight:600;">
                                {{ $shipper->country ?: '—' }}
                            </span>
                        </td>
                        <td style="max-width: 320px; white-space: normal; line-height: 1.4;">
                            {{ $shipper->address ?: '—' }}
                        </td>
                        <td>
                            @if($shipper->email)<div>{{ $shipper->email }}</div>@endif
                            @if($shipper->phone)<small class="muted-cell">{{ $shipper->phone }}</small>@endif
                            @if(!$shipper->email && !$shipper->phone)—@endif
                        </td>
                        <td>
                            @if($shipper->is_active)
                                <span class="status-badge status-active">Aktif</span>
                            @else
                                <span class="status-badge status-inactive">Nonaktif</span>
                            @endif
                        </td>
                        @can('customers.manage')
                            <td style="text-align: right;">
                                <div class="table-actions" style="justify-content: flex-end;">
                                    <a class="btn-action btn-action-primary" href="{{ route('customer-contacts.edit', $shipper) }}" title="Edit Shipper" data-tooltip="Edit">
                                        <x-icon name="edit"/>
                                    </a>
                                    <form method="POST" action="{{ route('customer-contacts.destroy', $shipper) }}" data-confirm="Hapus shipper {{ $shipper->name }}?" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="lock_version" value="{{ $shipper->lock_version }}">
                                        <input type="hidden" name="redirect_to" value="{{ route('customers.show', $customer) }}">
                                        <button type="submit" class="btn-action btn-action-danger" title="Hapus Shipper" data-tooltip="Hapus"><x-icon name="trash"/></button>
                                    </form>
                                </div>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->can('customers.manage') ? 6 : 5 }}">
                            <div class="empty-state">
                                <h3>Belum ada data Shipper</h3>
                                <p>Tambahkan daftar supplier / pengirim luar negeri untuk digunakan otomatis saat pembuatan penawaran (Quotation) & dokumen impor.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- ========================================================================= --}}
{{-- MASTER ALAMAT CUSTOMER                                                    --}}
{{-- ========================================================================= --}}
<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading">
        <div>
            <h2><x-icon name="map-pin" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Master Alamat Customer</h2>
            <span class="subtle">Lokasi gudang & tujuan pengiriman untuk Surat Jalan</span>
        </div>
        <div class="action-group">
            @can('customers.manage')
                <button type="button" class="button button-primary button-sm" onclick="document.getElementById('modal-add-customer-addr').showModal()">+ Tambah Alamat</button>
            @endcan
        </div>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Nama Lokasi</th>
                    <th>Alamat Lengkap</th>
                    <th>PIC / Telepon</th>
                    <th>Default</th>
                    <th>Status</th>
                    @can('customers.manage')
                        <th style="text-align: right;">Aksi</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @forelse($customer->addresses as $addr)
                    <tr>
                        <td><strong>{{ $addr->location_name }}</strong></td>
                        <td style="max-width: 320px; white-space: normal; line-height: 1.4;">{{ $addr->address }}</td>
                        <td>{{ $addr->pic_name ?: '—' }} @if($addr->pic_phone)<br><small class="muted-cell">{{ $addr->pic_phone }}</small>@endif</td>
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
                            <td style="text-align: right;">
                                <form method="POST" action="{{ route('customer-addresses.destroy', $addr) }}" data-confirm="Hapus alamat lokasi {{ $addr->location_name }}?" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action btn-action-danger" title="Hapus Alamat" data-tooltip="Hapus"><x-icon name="trash"/></button>
                                </form>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->can('customers.manage') ? 6 : 5 }}">
                            <div class="empty-state">
                                <h3>Belum ada alamat khusus</h3>
                                <p>Surat Jalan saat ini menggunakan alamat resmi perusahaan di atas. Tambahkan lokasi gudang/pabrik jika memiliki multi-alamat.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@can('customers.manage')
    {{-- MODAL TAMBAH CONSIGNEE (EXPORT) --}}
    <dialog id="modal-add-consignee" class="modal-dialog">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #e0e7ff; color: #4338ca; font-size: 20px; box-shadow: 0 2px 6px rgba(67,56,202,0.15);">
                    📦
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15.5px; font-weight: 700; color: #0f172a;">Tambah Consignee (Export)</h3>
                    <p style="margin: 2px 0 0; font-size: 11.5px; color: #64748b;">Penerima barang di luar negeri untuk kebutuhan ekspor.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-consignee').close()" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: grid; place-items: center; cursor: pointer; font-size: 14px; transition: all .15s ease;" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';" onmouseout="this.style.background='#fff';this.style.color='#64748b';">✕</button>
        </div>
        <form method="POST" action="{{ route('customer-contacts.store') }}" style="padding: 22px 24px;">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
            <input type="hidden" name="type" value="consignee">
            <input type="hidden" name="is_active" value="1">
            <input type="hidden" name="redirect_to" value="{{ route('customers.show', $customer) }}">
            <div style="display: grid; gap: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="field">
                        <label for="consignee_name" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nama Consignee <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="consignee_name" name="name" placeholder="Nama perorangan / kontak penerima" required maxlength="255">
                    </div>
                    <div class="field">
                        <label for="consignee_company" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Perusahaan / Company</label>
                        <input type="text" id="consignee_company" name="company" placeholder="Nama perusahaan penerima di LN" maxlength="255">
                    </div>
                </div>
                <div class="field">
                    <label for="consignee_country" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Negara Tujuan (Country)</label>
                    <input type="text" id="consignee_country" name="country" placeholder="contoh: Singapore / USA / Japan / Vietnam" maxlength="120">
                </div>
                <div class="field">
                    <label for="consignee_address" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Alamat Lengkap di Luar Negeri</label>
                    <textarea id="consignee_address" name="address" rows="3" placeholder="Alamat lengkap penerima luar negeri (untuk B/L, AWB, dll)..." maxlength="2000"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="field">
                        <label for="consignee_email" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Email</label>
                        <input type="email" id="consignee_email" name="email" placeholder="consignee@company.com" maxlength="255">
                    </div>
                    <div class="field">
                        <label for="consignee_phone" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Telepon / Kontak</label>
                        <input type="text" id="consignee_phone" name="phone" placeholder="+65..." maxlength="40">
                    </div>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('modal-add-consignee').close()">Batal</button>
                <button type="submit" class="button button-primary">
                    <x-icon name="check"/> Simpan Consignee
                </button>
            </div>
        </form>
    </dialog>

    {{-- MODAL TAMBAH SHIPPER (IMPORT) --}}
    <dialog id="modal-add-shipper" class="modal-dialog">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #fef3c7 100%);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #fef3c7; color: #b45309; font-size: 20px; box-shadow: 0 2px 6px rgba(180,83,9,0.15);">
                    🚢
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15.5px; font-weight: 700; color: #0f172a;">Tambah Shipper (Import)</h3>
                    <p style="margin: 2px 0 0; font-size: 11.5px; color: #64748b;">Pengirim barang / supplier di luar negeri untuk kebutuhan impor.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-shipper').close()" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: grid; place-items: center; cursor: pointer; font-size: 14px; transition: all .15s ease;" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';" onmouseout="this.style.background='#fff';this.style.color='#64748b';">✕</button>
        </div>
        <form method="POST" action="{{ route('customer-contacts.store') }}" style="padding: 22px 24px;">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
            <input type="hidden" name="type" value="shipper">
            <input type="hidden" name="is_active" value="1">
            <input type="hidden" name="redirect_to" value="{{ route('customers.show', $customer) }}">
            <div style="display: grid; gap: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="field">
                        <label for="shipper_name" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nama Shipper / Supplier <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="shipper_name" name="name" placeholder="Nama perorangan / kontak supplier" required maxlength="255">
                    </div>
                    <div class="field">
                        <label for="shipper_company" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Perusahaan / Company</label>
                        <input type="text" id="shipper_company" name="company" placeholder="Nama pabrik / supplier di LN" maxlength="255">
                    </div>
                </div>
                <div class="field">
                    <label for="shipper_country" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Negara Asal (Country)</label>
                    <input type="text" id="shipper_country" name="country" placeholder="contoh: China / Germany / USA / South Korea" maxlength="120">
                </div>
                <div class="field">
                    <label for="shipper_address" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Alamat Lengkap di Luar Negeri</label>
                    <textarea id="shipper_address" name="address" rows="3" placeholder="Alamat lengkap pabrik / gudang supplier di LN..." maxlength="2000"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="field">
                        <label for="shipper_email" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Email</label>
                        <input type="email" id="shipper_email" name="email" placeholder="supplier@company.com" maxlength="255">
                    </div>
                    <div class="field">
                        <label for="shipper_phone" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Telepon / Kontak</label>
                        <input type="text" id="shipper_phone" name="phone" placeholder="+86..." maxlength="40">
                    </div>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('modal-add-shipper').close()">Batal</button>
                <button type="submit" class="button button-primary">
                    <x-icon name="check"/> Simpan Shipper
                </button>
            </div>
        </form>
    </dialog>

    <dialog id="modal-add-customer-addr" class="modal-dialog">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #eff6ff; color: #2563eb; font-size: 20px; box-shadow: 0 2px 6px rgba(37,99,235,0.15);">
                    📍
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15.5px; font-weight: 700; color: #0f172a;">Tambah Alamat Pengiriman</h3>
                    <p style="margin: 2px 0 0; font-size: 11.5px; color: #64748b;">Lokasi gudang/depo customer untuk Surat Jalan.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-customer-addr').close()" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: grid; place-items: center; cursor: pointer; font-size: 14px; transition: all .15s ease;" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';" onmouseout="this.style.background='#fff';this.style.color='#64748b';">✕</button>
        </div>
        <form method="POST" action="{{ route('customer-addresses.store') }}" style="padding: 22px 24px;">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
            <input type="hidden" name="redirect_to" value="{{ route('customers.show', $customer) }}">
            <div style="display: grid; gap: 16px;">
                <div class="field">
                    <label for="cust_location_name" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nama Lokasi / Gudang / Label <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="cust_location_name" name="location_name" placeholder="contoh: Gudang Cikarang Barat / Pabrik Karawang" required maxlength="160">
                </div>
                <div class="field">
                    <label for="cust_address" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Alamat Lengkap <span style="color: #ef4444;">*</span></label>
                    <textarea id="cust_address" name="address" rows="3" placeholder="Jalan, Blok, Kawasan Industri, Kota..." required maxlength="5000"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="field">
                        <label for="cust_pic_name" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nama PIC Lokasi</label>
                        <input type="text" id="cust_pic_name" name="pic_name" placeholder="Nama penerima" maxlength="160">
                    </div>
                    <div class="field">
                        <label for="cust_pic_phone" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Telepon / HP PIC</label>
                        <input type="text" id="cust_pic_phone" name="pic_phone" placeholder="08..." maxlength="50">
                    </div>
                </div>
                <div class="field" style="display: flex; align-items: center; gap: 8px; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <input type="checkbox" id="cust_is_default" name="is_default" value="1" style="width: 16px; height: 16px;">
                    <label for="cust_is_default" style="font-size: 12.5px; margin: 0; font-weight: 600; color: #334155; cursor: pointer;">Jadikan Alamat Utama</label>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('modal-add-customer-addr').close()">Batal</button>
                <button type="submit" class="button button-primary">
                    <x-icon name="check"/> Simpan Alamat
                </button>
            </div>
        </form>
    </dialog>

    <script>
        document.querySelectorAll('dialog.modal-dialog').forEach(dialog => {
            dialog.addEventListener('click', (e) => {
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

<section class="panel">
    <div class="panel-heading">
        <h2>Dokumen</h2>
        <span class="subtle">NPWP & NIB</span>
    </div>
    @if($customer->documents->isNotEmpty())
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th style="width: 120px;">Jenis</th>
                        <th>Nama File</th>
                        <th>Diunggah Oleh</th>
                        <th>Diunggah Pada</th>
                        <th style="width: 90px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customer->documents as $document)
                        <tr>
                            <td>
                                <span class="status-badge status-approved">{{ \Illuminate\Support\Str::upper($document->type) }}</span>
                            </td>
                            <td>
                                <strong>{{ $document->original_name ?? $document->filename }}</strong>
                                @if($document->size)
                                    <br><small style="color: #64748b;">{{ $document->file_size_formatted }}</small>
                                @endif
                            </td>
                            <td>{{ $document->uploader?->name ?? '—' }}</td>
                            <td>{{ $document->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="table-actions" style="justify-content: flex-end;">
                                    <a class="btn-action btn-action-primary" href="{{ route('customers.documents.download', [$customer, $document]) }}" title="Unduh Dokumen" data-tooltip="Unduh" aria-label="Unduh Dokumen">
                                        <x-icon name="download"/>
                                    </a>
                                    @can('customers.manage')
                                        <form method="POST" action="{{ route('customers.documents.destroy', [$customer, $document]) }}" data-confirm="Hapus dokumen ini?">
                                            @csrf @method('DELETE')
                                            <button class="btn-action btn-action-danger" title="Hapus Dokumen" data-tooltip="Hapus" aria-label="Hapus Dokumen">
                                                <x-icon name="trash"/>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <x-icon name="file"/>
            <h3>Belum ada dokumen</h3>
            <p>Unggah NPWP/NIB pada form edit customer.</p>
        </div>
    @endif
</section>
@endsection
