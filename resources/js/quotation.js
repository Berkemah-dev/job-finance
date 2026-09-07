const form = document.querySelector('[data-quotation-form]');
if (form) {
    const container = form.querySelector('[data-items]');
    const template = form.querySelector('[data-item-template]');
    const addButton = form.querySelector('[data-add-item]');
    // Integer cents keep the preview exact, including fractional quantities.
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
        const rows = [...container.querySelectorAll('[data-quotation-item]')];
        let total = 0n, profit = 0n;
        rows.forEach((row, index) => {
            row.querySelector('.item-title').textContent = 'Item ' + (index + 1);
            row.querySelectorAll('[name]').forEach(input => input.name = input.name.replace(/items\[[^\]]+\]/, 'items[' + index + ']'));
            row.querySelectorAll('[id]').forEach(input => input.id = input.id.replace(/^item-[^-]+-/, 'item-' + index + '-'));
            row.querySelectorAll('label[for]').forEach(label => label.htmlFor = label.htmlFor.replace(/^item-[^-]+-/, 'item-' + index + '-'));
            const cost = row.querySelector('[data-unit-cost]');
            const price = row.querySelector('[data-unit-price]');
            const temporary = row.querySelector('[data-cost-type]').value === 'temporary';
            price.readOnly = temporary;
            if (temporary) price.value = cost.value;
            const quantity = cents(row.querySelector('[data-quantity]').value);
            const costTotal = (quantity * cents(cost.value) + 50n) / 100n;
            const sellTotal = (quantity * cents(price.value) + 50n) / 100n;
            total += sellTotal;
            if (!temporary) profit += sellTotal - costTotal;
            row.querySelector('[data-remove-item]').disabled = rows.length === 1;
        });
        form.querySelector('[data-preview-total]').textContent = rupiah(total);
        form.querySelector('[data-preview-profit]').textContent = rupiah(profit);
        addButton.disabled = rows.length >= 100;
    };
    addButton.addEventListener('click', () => {
        if (container.children.length >= 100) return;
        container.append(template.content.cloneNode(true));
        update();
        container.lastElementChild.querySelector('input').focus();
    });
    container.addEventListener('click', event => {
        if (event.target.closest('[data-remove-item]') && container.children.length > 1) {
            event.target.closest('[data-quotation-item]').remove();
            update();
        }
    });
    container.addEventListener('input', update);
    container.addEventListener('change', update);
    update();
}
