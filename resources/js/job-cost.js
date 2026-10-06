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
        const expression = new RegExp(`^\\d+(\\.\\d{0,${decimals}})?$`);
        if (!expression.test(cleaned)) return 0n;
        const [whole, fraction = ''] = cleaned.split('.');
        const multiplier = 10n ** BigInt(decimals);
        return BigInt(whole) * multiplier + BigInt(fraction.padEnd(decimals, '0'));
    };

    const rupiah = value => {
        const sign = value < 0n ? '-' : '';
        const absolute = value < 0n ? -value : value;
        return 'Rp ' + sign + (absolute / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + (absolute % 100n).toString().padStart(2, '0');
    };

    const sourceMoney = (value, currency) => {
        const sign = value < 0n ? '-' : '';
        const absolute = value < 0n ? -value : value;
        return `${currency} ${sign}${(absolute / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')}.${(absolute % 100n).toString().padStart(2, '0')}`;
    };

    const update = () => {
        const temporary = form.querySelector('[data-cost-type]').value === 'temporary';
        const cost = form.querySelector('[data-unit-cost]');
        const price = form.querySelector('[data-unit-price]');
        const currency = form.querySelector('[data-cost-currency]');
        const rate = form.querySelector('[data-exchange-rate]');
        const currencyCode = currency.value || 'IDR';
        const isIdr = currencyCode === 'IDR';
        rate.readOnly = isIdr;
        if (isIdr) rate.value = '1';
        form.querySelectorAll('[data-currency-code]').forEach(el => { el.textContent = currencyCode; });
        const rateHelp = form.querySelector('[data-exchange-help]');
        if (rateHelp) rateHelp.textContent = isIdr ? 'Untuk IDR, kurs otomatis 1.' : `Nilai 1 ${currencyCode} dalam Rupiah. Rate mingguan aktif menjadi nilai awal dan masih dapat diubah.`;
        price.readOnly = temporary;
        if (temporary) {
            price.value = cost.value;
        }
        const quantity = scaled(form.querySelector('[data-quantity]').value);
        const rateScaled = isIdr ? 10000n : scaled(rate.value, 4);
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
            sourcePreview.textContent = isIdr
                ? 'Nilai disimpan dan dijurnal dalam IDR.'
                : `Sumber: modal ${sourceMoney(sourceCost, currencyCode)} × kurs ${rate.value || '0'} = ${rupiah(idrUnitCost)} per unit.`;
        }
    };

    const weeklyRates = window.__weeklyRates || {};
    const existingCost = form.dataset.costExisting === 'true';
    const currency = form.querySelector('[data-cost-currency]');
    const rate = form.querySelector('[data-exchange-rate]');
    const applyWeeklyRate = () => {
        const currencyCode = currency.value || 'IDR';
        if (currencyCode === 'IDR') {
            rate.value = '1';
            return;
        }
        const weeklyRate = weeklyRates[currencyCode];
        if (weeklyRate !== undefined && weeklyRate !== null) {
            rate.value = String(weeklyRate);
        }
    };

    if (!existingCost && currency.value !== 'IDR' && (!rate.value || rate.value === '1')) {
        applyWeeklyRate();
    }
    currency.addEventListener('change', () => {
        applyWeeklyRate();
        update();
    });

    form.addEventListener('input', update);
    form.addEventListener('change', update);

    update();
}
