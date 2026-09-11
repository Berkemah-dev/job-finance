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
const setMenu = (open) => {
    document.body.classList.toggle('menu-open', open);
    menuToggle?.setAttribute('aria-expanded', String(open));
};
menuToggle?.addEventListener('click', () => setMenu(!document.body.classList.contains('menu-open')));
document.querySelector('[data-menu-close]')?.addEventListener('click', () => setMenu(false));
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') { setMenu(false); menuToggle?.focus(); }
});
document.querySelector('[data-password-toggle]')?.addEventListener('click', (event) => {
    const input = document.getElementById('password');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    event.currentTarget.setAttribute('aria-pressed', String(show));
    event.currentTarget.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
});
