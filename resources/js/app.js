import './bootstrap';
import './job-cost';
import './quotation';
import './journal';
import './customer';
import './coa';
import './custom-select';
document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) event.preventDefault();
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
