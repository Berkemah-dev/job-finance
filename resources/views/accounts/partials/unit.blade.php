<section class="panel coa-panel">
    <form class="filter-bar" method="GET" action="{{ route('accounts.index') }}">
        <input type="hidden" name="tab" value="unit">
        <input name="search" value="{{ $search }}" placeholder="Cari nama satuan..." aria-label="Cari satuan">
        <button class="button button-primary" type="submit">Cari</button>
        <a class="text-link" href="{{ route('accounts.index', ['tab'=>'unit']) }}">Reset</a>
    </form>
    
    <form method="POST" action="{{ route('container-units.store') }}" style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;padding:14px 20px;border-bottom:1px solid #edf2f7;background:#fbfcfe;">
        @csrf
        <input name="name" placeholder="Nama Satuan (mis: 40GP)" required maxlength="50" style="padding:0.5rem 0.75rem;border:1px solid var(--border);border-radius:6px;min-width:260px;background:var(--surface);color:var(--text);font-size:12px;">
        <button class="button button-primary" type="submit">+ Tambah Satuan</button>
    </form>

    <div class="coa-filter-strip">
        <div class="coa-tree-info">
            <span class="coa-count-text">Menampilkan <strong>{{ $units->total() }}</strong> data unit (satuan)</span>
        </div>
    </div>

    <div class="coa-table-wrapper">
        <table class="coa-tree-table">
            <thead>
                <tr>
                    <th scope="col">DATA UNIT (SATUAN)</th>
                    <th scope="col" style="width: 100px;">STATUS</th>
                    <th scope="col" class="col-actions">EDIT</th>
                    <th scope="col" class="col-actions">DEL</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $unit)
                    <tr class="coa-row">
                        <td class="coa-cell-name">
                            <div class="coa-node">
                                <span class="coa-folder-icon" aria-hidden="true">
                                    <x-icon name="box" class="coa-icon-folder"/>
                                </span>
                                <span class="coa-label coa-root-label">
                                    <strong>{{ $unit->name }}</strong>
                                </span>
                            </div>
                        </td>
                        <td>
                            @if($unit->is_active)
                                <span class="status-badge status-paid">Aktif</span>
                            @else
                                <span class="status-badge status-cancelled">Nonaktif</span>
                            @endif
                        </td>
                        <td class="col-actions">
                            <a class="coa-action-button" href="{{ route('container-units.edit', $unit) }}" title="Edit Satuan">
                                <x-icon name="edit"/>
                            </a>
                        </td>
                        <td class="col-actions">
                            <form method="POST" action="{{ route('container-units.destroy', $unit) }}" onsubmit="return confirm('Hapus satuan ini?')">
                                @csrf @method('DELETE')
                                <button class="coa-action-button coa-btn-del" type="submit" title="Hapus">
                                    <x-icon name="x"/>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">
                            <div class="empty-icon"><x-icon name="file"/></div>
                            <h3>Belum ada satuan</h3>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($units, 'links') && $units->hasPages())
        <div class="pagination">{{ $units->links() }}</div>
    @endif
</section>
