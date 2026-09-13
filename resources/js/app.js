import './bootstrap';
import './job-cost';
import './quotation';
import './journal';
import './customer';
import './coa';
import './custom-select';
import './dashboard-charts';
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-toast]').forEach((toast) => {
        const close = () => toast.remove();
        toast.querySelector('.app-toast-close')?.addEventListener('click', close);
        setTimeout(close, 5200);
    });
});

let pendingConfirmForm = null;
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
        pendingConfirmForm = null;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    }

    if (event.target.matches('[data-confirm-ok]') && pendingConfirmForm) {
        const form = pendingConfirmForm;
        pendingConfirmForm = null;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        form.dataset.confirmed = 'true';
        form.requestSubmit();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const modal = document.querySelector('[data-confirm-modal]');
    if (!modal?.classList.contains('show')) return;
    pendingConfirmForm = null;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
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

initSidebar();
window.addEventListener('resize', initSidebar);
document.querySelector('[data-password-toggle]')?.addEventListener('click', (event) => {
    const input = document.getElementById('password');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    event.currentTarget.setAttribute('aria-pressed', String(show));
    event.currentTarget.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
});
