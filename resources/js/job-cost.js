// Helper pembersih format angka
export const cleanNumber = value => {
    if (!value) return '0';
    let v = value.toString().trim();
    v = v.replace(/^Rp\s*/i, '').trim();
    if (v.includes('.') && v.includes(',')) {
        if (v.lastIndexOf(',') > v.lastIndexOf('.')) {
            v = v.replace(/\./g, '').replace(',', '.');
        } else {
            v = v.replace(/,/g, '');
        }
    } else if (v.includes('.')) {
        if ((v.match(/\./g) || []).length > 1 || /\.\d{3}$/.test(v)) {
            v = v.replace(/\./g, '');
        }
    } else if (v.includes(',')) {
        if ((v.match(/,/g) || []).length > 1 || /,\d{3}$/.test(v)) {
            v = v.replace(/,/g, '');
        } else {
            v = v.replace(',', '.');
        }
    }
    return v.replace(/[^0-9.]/g, '');
};

const formatInputLive = input => {
    let val = input.value;
    if (!val) return;

    let cursorPos = input.selectionStart || 0;
    let digitsBeforeCursor = val.slice(0, cursorPos).replace(/\D/g, '').length;

    let parts = val.split(',');
    let intPart = parts[0].replace(/\D/g, '');

    // Hapus angka 0 di depan jika diikuti angka lain (contoh: 0500000 -> 500000)
    if (intPart.length > 1 && intPart.startsWith('0')) {
        intPart = intPart.replace(/^0+/, '');
        if (intPart === '') intPart = '0';
    }

    let formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    let newVal = formattedInt;
    if (parts.length > 1) {
        let decPart = parts[1].replace(/\D/g, '').slice(0, 2);
        newVal += ',' + decPart;
    }

    if (input.value !== newVal) {
        input.value = newVal;
        let newPos = 0;
        let count = 0;
        for (let i = 0; i < newVal.length; i++) {
            if (/\d/.test(newVal[i])) count++;
            if (count >= digitsBeforeCursor) {
                newPos = i + 1;
                break;
            }
        }
        if (digitsBeforeCursor === 0) newPos = 0;
        input.setSelectionRange(newPos, newPos);
    }
};

export const initCurrencyInputs = (container = document) => {
    const currencyInputs = container.querySelectorAll('[data-currency-input]');
    currencyInputs.forEach(input => {
        if (input.dataset.currencyBound) return;
        input.dataset.currencyBound = 'true';

        // Auto-select jika bernilai 0 saat diklik/fokus
        input.addEventListener('focus', function() {
            if (this.value === '0' || this.value === '0,00' || this.value === '0.00') {
                this.select();
            }
        });

        // Format titik real-time seketika saat tombol angka ditekan
        input.addEventListener('input', function() {
            formatInputLive(this);
        });

        input.addEventListener('blur', function() {
            if (this.value) {
                formatInputLive(this);
            }
        });

        // Format nilai awal jika sudah ada isinya
        if (input.value && input.value !== '0') {
            formatInputLive(input);
        }
    });
};

// Jalankan otomatis untuk semua data-currency-input
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initCurrencyInputs());
} else {
    initCurrencyInputs();
}

// Logika khusus form kalkulasi biaya job (jika ada form data-cost-form)
const form = document.querySelector('[data-cost-form]');
if (form) {
    const scaled = (value, decimals = 2) => {
        const cleaned = cleanNumber(value);
        if (!/^\d+(\.\d+)?$/.test(cleaned)) return 0n;
        const [whole, fraction = ''] = cleaned.split('.');
        return BigInt(whole) * (10n ** BigInt(decimals)) + BigInt(fraction.slice(0, decimals).padEnd(decimals, '0'));
    };

    const rupiah = value => {
        const sign = value < 0n ? '-' : '';
        const absolute = value < 0n ? -value : value;
        return 'Rp ' + sign + (absolute / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + (absolute % 100n).toString().padStart(2, '0');
    };

    const foreignMoney = (currency, value) => {
        const whole = value / 100n;
        const fraction = (value % 100n).toString().padStart(2, '0');
        return `${currency} ${whole.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')}.${fraction}`;
    };

    const update = () => {
        const temporary = form.querySelector('[data-cost-type]').value === 'temporary';
        const cost = form.querySelector('[data-unit-cost]');
        const price = form.querySelector('[data-unit-price]');
        const currency = form.querySelector('[data-cost-currency]');
        const rate = form.querySelector('[data-exchange-rate]');
        price.readOnly = temporary;
        if (temporary) {
            price.value = cost.value;
        }
        const isIdr = currency.value === 'IDR';
        rate.readOnly = isIdr;
        if (isIdr) rate.value = '1';
        form.querySelectorAll('[data-currency-code]').forEach(node => { node.textContent = currency.value; });
        const rateHelp = form.querySelector('[data-rate-help]');
        if (rateHelp) rateHelp.textContent = isIdr ? 'Kurs 1,00 untuk IDR.' : `1 ${currency.value} = ${rate.value || '0'} IDR`;

        const quantity = scaled(form.querySelector('[data-quantity]').value);
        const rateScaled = scaled(rate.value, 4);
        const sourceCost = scaled(cost.value);
        const sourcePrice = scaled(price.value);
        const idrUnitCost = (sourceCost * rateScaled + 5000n) / 10000n;
        const idrUnitPrice = (sourcePrice * rateScaled + 5000n) / 10000n;
        const totalCost = (quantity * idrUnitCost + 50n) / 100n;
        const totalPrice = (quantity * idrUnitPrice + 50n) / 100n;
        form.querySelector('[data-preview-cost]').textContent = rupiah(totalCost);
        form.querySelector('[data-preview-total]').textContent = rupiah(totalPrice);
        form.querySelector('[data-preview-profit]').textContent = rupiah(temporary ? 0n : totalPrice - totalCost);
        const sourcePreview = form.querySelector('[data-preview-source]');
        if (sourcePreview) {
            const sourceTotalCost = (quantity * sourceCost + 50n) / 100n;
            const sourceTotalPrice = (quantity * sourcePrice + 50n) / 100n;
            sourcePreview.textContent = isIdr ? '' : `Nilai asal: modal ${foreignMoney(currency.value, sourceTotalCost)} · jual ${foreignMoney(currency.value, sourceTotalPrice)}.`;
        }
    };

    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
}
