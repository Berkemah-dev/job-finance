const form = document.querySelector('[data-quotation-form]');
if (form) {
    const container = form.querySelector('[data-items]');
    const template = form.querySelector('[data-item-template]');
    const addButton = form.querySelector('[data-add-item]');
    const endpoint = form.dataset.pricingEndpoint;
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
    const showStatus = (row, message, error = false) => {
        const status = row.querySelector('[data-pricing-status]');
        status.textContent = message;
        status.classList.toggle('is-error', error);
    };
    const setSource = (row, source) => {
        row.querySelector('[data-pricing-source]').value = source;
        row.querySelector('[data-field-pricing-source]').value = source;
        const trucking = source === 'trucking';
        row.querySelectorAll('[data-lookup-only]').forEach(el => { el.hidden = !trucking; });
        const cost = row.querySelector('[data-unit-cost]');
        cost.readOnly = trucking && row.querySelector('[data-field-pricing-id]').value !== '';
        if (!trucking) showStatus(row, '');
    };
    const applySuggestion = (row, data) => {
        row.querySelector('[data-field-pricing-id]').value = data.pricing_id;
        row.querySelector('[data-field-currency]').value = data.currency;
        row.querySelector('[data-field-exchange-rate]').value = data.exchange_rate;
        row.querySelector('[data-field-snapshot]').value = JSON.stringify(data.snapshot);
        const cost = row.querySelector('[data-unit-cost]');
        cost.value = data.unit_cost;
        cost.readOnly = true;
        const conversion = data.currency !== 'IDR' ? ' · dari ' + data.currency + ' @ ' + data.exchange_rate : '';
        showStatus(row, 'Tarif ' + data.port_origin + ' → ' + data.destination + ' ' + data.container_type.toUpperCase() + (data.overweight ? ' (overweight)' : '') + ': ' + rupiah(cents(data.unit_cost)) + conversion + '. Modal terisi dari master tarif.');
    };
    const searchPricing = async row => {
        const origin = row.querySelector('[data-origin]').value.trim();
        const destination = row.querySelector('[data-destination]').value.trim();
        const containerType = row.querySelector('[data-container]').value;
        if (!origin || !destination || !containerType) {
            showStatus(row, 'Lengkapi pelabuhan asal, tujuan, dan jenis kontainer.', true);
            return;
        }
        const params = new URLSearchParams({
            port_origin: origin,
            destination,
            container_type: containerType,
            overweight: row.querySelector('[data-overweight]').checked ? '1' : '0',
        });
        let response;
        try {
            response = await fetch(endpoint + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        } catch {
            showStatus(row, 'Gagal menghubungi server.', true);
            return;
        }
        if (response.status === 403) {
            showStatus(row, 'Tidak memiliki akses tarif.', true);
            return;
        }
        const data = await response.json();
        if (!data.found) {
            row.querySelector('[data-field-pricing-id]').value = '';
            row.querySelector('[data-field-snapshot]').value = '';
            showStatus(row, 'Tarif trucking tidak ditemukan untuk rute tersebut.', true);
            return;
        }
        applySuggestion(row, data);
    };
    const update = () => {
        const rows = [...container.querySelectorAll('[data-quotation-item]')];
        let total = 0n, profit = 0n;
        rows.forEach((row, index) => {
            row.querySelector('.item-title').textContent = 'Item ' + (index + 1);
            row.querySelectorAll('[name]').forEach(input => input.name = input.name.replace(/items\[[^\]]+\]/, 'items[' + index + ']'));
            row.querySelectorAll('[id]').forEach(input => input.id = input.id.replace(/^item-[^-]+-/, 'item-' + index + '-'));
            row.querySelectorAll('label[for]').forEach(label => label.htmlFor = label.htmlFor.replace(/^item-[^-]+-/, 'item-' + index + '-'));
            setSource(row, row.querySelector('[data-pricing-source]').value);
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
        const row = container.lastElementChild;
        setSource(row, 'manual');
        update();
        row.querySelector('[data-quantity]').focus();
    });
    container.addEventListener('click', event => {
        const target = event.target.closest('[data-pricing-search]');
        if (target) {
            searchPricing(target.closest('[data-quotation-item]'));
            return;
        }
        if (event.target.closest('[data-remove-item]') && container.children.length > 1) {
            event.target.closest('[data-quotation-item]').remove();
            update();
        }
    });
    container.addEventListener('change', event => {
        if (event.target.matches('[data-pricing-source]')) setSource(event.target.closest('[data-quotation-item]'), event.target.value);
    });
    container.addEventListener('input', update);
    container.addEventListener('change', update);
    update();
}