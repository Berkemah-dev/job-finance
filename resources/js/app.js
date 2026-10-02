import './bootstrap';
import './job-cost';
import './quotation';
import './journal';
import './customer';
import './coa';
import './custom-select';
import './dashboard-charts';
// Modern Toast Notification System
window.showToast = function(message, type = 'info', title = null, duration = 4500) {
    let wrap = document.querySelector('.app-toast-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.className = 'app-toast-wrap';
        document.body.appendChild(wrap);
    }

    const toast = document.createElement('div');
    toast.className = `app-toast ${type}`;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.setAttribute('data-toast', '');

    const iconChar = type === 'success' ? '✓' : (type === 'error' ? '✕' : (type === 'warning' ? '!' : 'ℹ'));
    const defaultTitle = type === 'success' ? 'Berhasil' : (type === 'error' ? 'Terjadi Kesalahan' : (type === 'warning' ? 'Perhatian' : 'Informasi'));

    toast.innerHTML = `
        <span class="app-toast-icon">${iconChar}</span>
        <div style="flex:1;">
            <strong>${title || defaultTitle}</strong>
            <p>${message}</p>
        </div>
        <button class="app-toast-close" type="button" aria-label="Tutup">×</button>
    `;

    wrap.appendChild(toast);

    const close = () => {
        toast.style.animation = 'toastOut .22s ease forwards';
        setTimeout(() => toast.remove(), 220);
    };

    toast.querySelector('.app-toast-close')?.addEventListener('click', close);
    setTimeout(close, duration);
};

// Modern Alert Modal System
window.appAlert = function(message, title = 'Perhatian', options = {}) {
    return new Promise((resolve) => {
        const modal = document.querySelector('[data-alert-modal]');
        if (!modal) {
            window.showToast(message, options.type || 'warning', title);
            if (options.focusEl) {
                const target = options.focusEl.closest('.custom-select-wrapper')?.querySelector('.custom-select-trigger') || options.focusEl;
                target.focus();
                target.classList.add('field-invalid-shake');
                setTimeout(() => target.classList.remove('field-invalid-shake'), 1200);
            }
            resolve(true);
            return;
        }

        const type = options.type || 'warning';
        const iconEl = modal.querySelector('[data-alert-icon]');
        const titleEl = modal.querySelector('[data-alert-title]');
        const messageEl = modal.querySelector('[data-alert-message]');
        const closeBtn = modal.querySelector('[data-alert-close]');

        if (titleEl) titleEl.textContent = title;
        if (messageEl) messageEl.textContent = message;
        if (iconEl) {
            iconEl.className = `confirm-icon ${type}`;
            iconEl.textContent = type === 'success' ? '✓' : (type === 'error' ? '✕' : (type === 'info' ? 'ℹ' : '!'));
        }
        if (closeBtn) {
            closeBtn.textContent = options.buttonText || 'Mengerti';
        }

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        closeBtn?.focus();

        const dismiss = () => {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            if (options.focusEl) {
                try {
                    const target = options.focusEl.closest('.custom-select-wrapper')?.querySelector('.custom-select-trigger') || options.focusEl;
                    target.focus();
                    target.classList.add('field-invalid-shake');
                    setTimeout(() => target.classList.remove('field-invalid-shake'), 1200);
                } catch (e) {}
            }
            resolve(true);
        };

        const onKey = (e) => {
            if (e.key === 'Escape' || e.key === 'Enter') {
                document.removeEventListener('keydown', onKey);
                dismiss();
            }
        };
        document.addEventListener('keydown', onKey);

        closeBtn.onclick = () => {
            document.removeEventListener('keydown', onKey);
            dismiss();
        };

        modal.onclick = (e) => {
            if (e.target === modal) {
                document.removeEventListener('keydown', onKey);
                dismiss();
            }
        };
    });
};

// Global window.alert override
window.alert = function(message) {
    window.appAlert(message, 'Perhatian');
};

// Modern Confirm Modal System
let pendingConfirmForm = null;
let pendingConfirmResolve = null;

window.appConfirm = function(message, title = 'Konfirmasi aksi', options = {}) {
    return new Promise((resolve) => {
        const modal = document.querySelector('[data-confirm-modal]');
        if (!modal) {
            resolve(window.confirm(message));
            return;
        }

        const titleEl = modal.querySelector('[data-confirm-title]') || modal.querySelector('#confirm-title');
        const messageEl = modal.querySelector('[data-confirm-message]');
        const cancelBtn = modal.querySelector('[data-confirm-cancel]');
        const okBtn = modal.querySelector('[data-confirm-ok]');

        if (titleEl) titleEl.textContent = title;
        if (messageEl) messageEl.textContent = message;
        if (okBtn) okBtn.textContent = options.confirmText || 'Lanjut';
        if (cancelBtn) cancelBtn.textContent = options.cancelText || 'Batal';

        pendingConfirmResolve = resolve;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        okBtn?.focus();
    });
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-toast]').forEach((toast) => {
        const close = () => {
            toast.style.animation = 'toastOut .22s ease forwards';
            setTimeout(() => toast.remove(), 220);
        };
        toast.querySelector('.app-toast-close')?.addEventListener('click', close);
        setTimeout(close, 5200);
    });
});

document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;
    if (!message || event.target.dataset.confirmed === 'true') return;

    const modal = document.querySelector('[data-confirm-modal]');
    if (!modal) {
        if (!window.confirm(message)) event.preventDefault();
        return;
    }

    event.preventDefault();
    pendingConfirmForm = event.target;
    modal.querySelector('[data-confirm-message]').textContent = message;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    modal.querySelector('[data-confirm-ok]')?.focus();
});

document.addEventListener('click', (event) => {
    const modal = document.querySelector('[data-confirm-modal]');
    if (!modal) return;

    if (event.target.matches('[data-confirm-cancel]') || event.target === modal) {
        const resolve = pendingConfirmResolve;
        pendingConfirmResolve = null;
        pendingConfirmForm = null;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        if (resolve) resolve(false);
    }

    if (event.target.matches('[data-confirm-ok]')) {
        const resolve = pendingConfirmResolve;
        const form = pendingConfirmForm;
        pendingConfirmResolve = null;
        pendingConfirmForm = null;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        if (resolve) resolve(true);
        if (form) {
            form.dataset.confirmed = 'true';
            form.requestSubmit();
        }
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const modal = document.querySelector('[data-confirm-modal]');
    if (!modal?.classList.contains('show')) return;
    const resolve = pendingConfirmResolve;
    pendingConfirmResolve = null;
    pendingConfirmForm = null;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    if (resolve) resolve(false);
});
import '@fontsource/poppins/400.css';
import '@fontsource/poppins/500.css';
import '@fontsource/poppins/600.css';
import '@fontsource/poppins/700.css';

const menuToggle = document.querySelector('[data-menu-toggle]');
const sidebarNav = document.querySelector('.sidebar nav') || document.getElementById('sidebar-nav');
const isDesktop = () => window.innerWidth > 900;

const setDesktopCollapsed = (collapsed) => {
    document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    localStorage.setItem('jobfinance_sidebar_collapsed', collapsed ? '1' : '0');
    menuToggle?.setAttribute('aria-expanded', String(!collapsed));
};

const initSidebar = () => {
    if (isDesktop()) {
        const isCollapsed = localStorage.getItem('jobfinance_sidebar_collapsed') === '1';
        setDesktopCollapsed(isCollapsed);
    } else {
        document.documentElement.classList.remove('sidebar-collapsed');
        document.body.classList.remove('sidebar-collapsed');
        menuToggle?.setAttribute('aria-expanded', String(document.body.classList.contains('menu-open')));
    }
};

const toggleSidebar = () => {
    if (isDesktop()) {
        const currentlyCollapsed = document.documentElement.classList.contains('sidebar-collapsed') || document.body.classList.contains('sidebar-collapsed');
        setDesktopCollapsed(!currentlyCollapsed);
    } else {
        const isOpen = document.body.classList.toggle('menu-open');
        menuToggle?.setAttribute('aria-expanded', String(isOpen));
    }
};

const closeMobileSidebar = () => {
    if (!isDesktop() && document.body.classList.contains('menu-open')) {
        document.body.classList.remove('menu-open');
        menuToggle?.setAttribute('aria-expanded', 'false');
    }
};

menuToggle?.addEventListener('click', toggleSidebar);
document.querySelector('[data-menu-close]')?.addEventListener('click', closeMobileSidebar);

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeMobileSidebar();
        menuToggle?.focus();
    }
});

// Sidebar Scroll Position Persistence across page reloads & navigations
if (sidebarNav) {
    const restoreScroll = () => {
        const savedScroll = sessionStorage.getItem('jobfinance_sidebar_scroll');
        if (savedScroll !== null) {
            sidebarNav.scrollTop = parseInt(savedScroll, 10);
        } else {
            const activeItem = sidebarNav.querySelector('.nav-item.active');
            if (activeItem) {
                activeItem.scrollIntoView({ block: 'nearest' });
            }
        }
    };

    // Restore scroll immediately and on load
    restoreScroll();
    window.addEventListener('load', restoreScroll);

    // Save scroll position on scroll
    let scrollDebounce;
    sidebarNav.addEventListener('scroll', () => {
        clearTimeout(scrollDebounce);
        scrollDebounce = setTimeout(() => {
            sessionStorage.setItem('jobfinance_sidebar_scroll', String(sidebarNav.scrollTop));
        }, 50);
    }, { passive: true });

    // Save scroll position immediately when clicking any menu link
    sidebarNav.querySelectorAll('a.nav-item').forEach((link) => {
        link.addEventListener('click', () => {
            sessionStorage.setItem('jobfinance_sidebar_scroll', String(sidebarNav.scrollTop));
            if (!isDesktop()) {
                closeMobileSidebar();
            }
        });
    });
}

// Sidebar Nav Category Group Open State Persistence
const navGroups = document.querySelectorAll('details.nav-group');
if (navGroups.length) {
    const STORAGE_KEY = 'jobfinance_sidebar_open_groups';
    try {
        const savedOpenGroups = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
        navGroups.forEach((group) => {
            const title = group.querySelector('.nav-group-title span')?.textContent?.trim();
            const hasActive = group.querySelector('.nav-item.active') !== null;
            if (hasActive) {
                group.open = true;
            } else if (title && savedOpenGroups.includes(title)) {
                group.open = true;
            }
        });
    } catch (e) {}

    const saveGroupStates = () => {
        if (document.documentElement.classList.contains('sidebar-collapsed')) return;
        const openTitles = [];
        navGroups.forEach((group) => {
            if (group.open) {
                const title = group.querySelector('.nav-group-title span')?.textContent?.trim();
                if (title) openTitles.push(title);
            }
        });
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(openTitles));
    };

    navGroups.forEach((group) => {
        group.addEventListener('toggle', saveGroupStates);
    });
}

initSidebar();
window.addEventListener('resize', initSidebar);
document.querySelector('[data-password-toggle]')?.addEventListener('click', (event) => {
    const input = document.getElementById('password');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    event.currentTarget.setAttribute('aria-pressed', String(show));
    event.currentTarget.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
});
