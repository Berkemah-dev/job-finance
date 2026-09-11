<section class="panel coa-panel">
    <form class="filter-bar" method="GET" action="{{ route('accounts.index') }}">
        <input type="hidden" name="tab" value="charge">
        <input name="search" value="{{ $search }}" placeholder="Cari nama biaya..." aria-label="Cari jenis biaya">
        <select name="status" aria-label="Status biaya">
            <option value="all" {{ $status==='all'?'selected':'' }}>Semua Status</option>
            <option value="active" {{ $status==='active'?'selected':'' }}>Aktif</option>
            <option value="inactive" {{ $status==='inactive'?'selected':'' }}>Nonaktif</option>
        </select>
        <button class="button button-primary" type="submit">Cari</button>
        <a class="text-link" href="{{ route('accounts.index', ['tab'=>'charge']) }}">Reset</a>
    </form>
    
    <form method="POST" action="{{ route('charge-types.store') }}" style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;padding:14px 20px;border-bottom:1px solid #edf2f7;background:#fbfcfe;">
        @csrf
        <input name="name" placeholder="Nama Jenis Biaya (mis: TRUCKING)" required maxlength="150" style="padding:0.5rem 0.75rem;border:1px solid var(--border);border-radius:6px;min-width:320px;background:var(--surface);color:var(--text);font-size:12px;">
        <button class="button button-primary" type="submit">+ Tambah Jenis Biaya</button>
    </form>

    <div class="coa-filter-strip">
        <div class="coa-tree-info">
            <span class="coa-count-text">Menampilkan <strong>{{ $charges->total() }}</strong> data cost (jenis biaya)</span>
        </div>
    </div>

    <div class="coa-table-wrapper">
        <table class="coa-tree-table">
            <thead>
                <tr>
                    <th scope="col">DATA COST (NAMA BIAYA)</th>
                    <th scope="col" style="width: 100px;">STATUS</th>
                    <th scope="col" class="col-actions">EDIT</th>
                    <th scope="col" class="col-actions">DEL</th>
                </tr>
            </thead>
            <tbody>
                @forelse($charges as $charge)
                    <tr class="coa-row">
                        <td class="coa-cell-name">
                            <div class="coa-node">
                                <span class="coa-folder-icon" aria-hidden="true">
                                    <x-icon name="dollar-sign" class="coa-icon-folder"/>
                                </span>
                                <span class="coa-label coa-root-label">
                                    <strong>{{ $charge->name }}</strong>
                                </span>
                            </div>
                        </td>
                        <td>
                            @if($charge->is_active)
                                <span class="status-badge status-paid">Aktif</span>
                            @else
                                <span class="status-badge status-cancelled">Nonaktif</span>
                            @endif
                        </td>
                        <td class="col-actions">
                            <a class="coa-action-button" href="{{ route('charge-types.edit', $charge) }}" title="Edit Jenis Biaya">
                                <x-icon name="edit"/>
                            </a>
                        </td>
                        <td class="col-actions">
                            <form method="POST" action="{{ route('charge-types.destroy', $charge) }}" onsubmit="return confirm('Hapus jenis biaya ini?')">
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
                            <h3>Belum ada jenis biaya</h3>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($charges, 'links') && $charges->hasPages())
        <div class="pagination">{{ $charges->links() }}</div>
    @endif
</section>
