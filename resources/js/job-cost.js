const form = document.querySelector('[data-cost-form]');
if (form) {
    const cents = value => {
        if (!/^\d+(\.\d{0,2})?$/.test(value)) return 0n;
        const [whole, fraction = ''] = value.split('.');
        return BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0'));
    };
    const rupiah = value => {
        const sign = value < 0n ? '-' : '';
        const absolute = value < 0n ? -value : value;
        return 'Rp ' + sign + (absolute / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + (absolute % 100n).toString().padStart(2, '0');
    };
    const update = () => {
        const temporary = form.querySelector('[data-cost-type]').value === 'temporary';
        const cost = form.querySelector('[data-unit-cost]');
        const price = form.querySelector('[data-unit-price]');
        price.readOnly = temporary;
        if (temporary) price.value = cost.value;
        const quantity = cents(form.querySelector('[data-quantity]').value);
        const totalCost = (quantity * cents(cost.value) + 50n) / 100n;
        const totalPrice = (quantity * cents(price.value) + 50n) / 100n;
        form.querySelector('[data-preview-cost]').textContent = rupiah(totalCost);
        form.querySelector('[data-preview-total]').textContent = rupiah(totalPrice);
        form.querySelector('[data-preview-profit]').textContent = rupiah(temporary ? 0n : totalPrice - totalCost);
    };
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
}
