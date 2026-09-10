// Chart of Accounts (COA) Tree View Expand / Collapse ("Buka / Tutup")

const initCoaTree = () => {
    const table = document.querySelector('.coa-tree-table');
    if (!table) return;

    const rows = Array.from(table.querySelectorAll('tbody tr.coa-row'));
    if (!rows.length) return;

    const rowMap = new Map();
    rows.forEach((row) => {
        const id = row.dataset.coaId;
        if (id) {
            rowMap.set(id, row);
        }
    });

    const STORAGE_KEY = 'jobfinance_coa_collapsed';
    let collapsedIds = new Set();

    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            const parsed = JSON.parse(saved);
            if (Array.isArray(parsed)) {
                collapsedIds = new Set(parsed.map(String));
            }
        }
    } catch {
        collapsedIds = new Set();
    }

    const saveState = () => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(collapsedIds)));
        } catch {
            // Ignore quota or private browsing errors
        }
    };

    const isAncestorCollapsed = (parentId) => {
        let currentParentId = parentId;
        while (currentParentId) {
            if (collapsedIds.has(String(currentParentId))) {
                return true;
            }
            const parentRow = rowMap.get(String(currentParentId));
            currentParentId = parentRow ? parentRow.dataset.coaParentId : null;
        }
        return false;
    };

    const updateVisibility = () => {
        rows.forEach((row) => {
            const id = row.dataset.coaId;
            const parentId = row.dataset.coaParentId;
            const hasChildren = row.dataset.coaHasChildren === '1';

            if (parentId && isAncestorCollapsed(parentId)) {
                row.classList.add('coa-row-hidden');
            } else {
                row.classList.remove('coa-row-hidden');
            }

            if (hasChildren) {
                const isCollapsed = collapsedIds.has(String(id));
                row.classList.toggle('is-collapsed', isCollapsed);
                const toggleBtn = row.querySelector('[data-coa-toggle]');
                if (toggleBtn) {
                    toggleBtn.setAttribute('aria-expanded', String(!isCollapsed));
                    toggleBtn.setAttribute(
                        'title',
                        isCollapsed ? 'Klik untuk buka sub akun' : 'Klik untuk tutup sub akun'
                    );
                }
            }
        });
    };

    const toggleRow = (id) => {
        const stringId = String(id);
        if (collapsedIds.has(stringId)) {
            collapsedIds.delete(stringId);
        } else {
            collapsedIds.add(stringId);
        }
        saveState();
        updateVisibility();
    };

    // Event delegation on table body
    table.querySelector('tbody')?.addEventListener('click', (event) => {
        // Prevent toggle if clicking action links or buttons inside actions column
        if (event.target.closest('.col-actions') || event.target.closest('a') || event.target.closest('form')) {
            return;
        }

        const toggleBtn = event.target.closest('[data-coa-toggle]');
        const folderIcon = event.target.closest('.coa-folder-icon');
        const nodeLabel = event.target.closest('.coa-label-interactive');

        if (toggleBtn || folderIcon || nodeLabel) {
            const row = (toggleBtn || folderIcon || nodeLabel).closest('tr.coa-row');
            if (row && row.dataset.coaHasChildren === '1') {
                event.preventDefault();
                toggleRow(row.dataset.coaId);
            }
        }
    });

    // Double click on node to toggle
    table.querySelector('tbody')?.addEventListener('dblclick', (event) => {
        if (event.target.closest('.col-actions') || event.target.closest('a') || event.target.closest('form')) {
            return;
        }
        const row = event.target.closest('tr.coa-row');
        if (row && row.dataset.coaHasChildren === '1') {
            toggleRow(row.dataset.coaId);
        }
    });

    // Expand All ("Buka Semua")
    document.getElementById('coa-expand-all')?.addEventListener('click', () => {
        collapsedIds.clear();
        saveState();
        updateVisibility();
    });

    // Collapse All ("Tutup Semua")
    document.getElementById('coa-collapse-all')?.addEventListener('click', () => {
        rows.forEach((row) => {
            if (row.dataset.coaHasChildren === '1') {
                collapsedIds.add(String(row.dataset.coaId));
            }
        });
        saveState();
        updateVisibility();
    });

    // Initial render
    updateVisibility();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCoaTree);
} else {
    initCoaTree();
}
