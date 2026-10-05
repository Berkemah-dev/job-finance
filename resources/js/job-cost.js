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
    const currencySelect = form.querySelector('[data-cost-currency]');
    const rateInput = form.querySelector('[data-cost-exchange-rate]');
    const rateHelp = form.querySelector('#rate_help');
    const labelCurrencies = form.querySelectorAll('[data-label-currency]');

    const parseNum = value => {
        const cleaned = cleanNumber(value);
        return parseFloat(cleaned) || 0;
    };

    const formatIdr = value => {
        const sign = value < 0 ? '-' : '';
        const abs = Math.abs(value);
        return 'Rp ' + sign + abs.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const formatForeign = (currency, value) => {
        const sign = value < 0 ? '-' : '';
        const abs = Math.abs(value);
        return currency + ' ' + sign + abs.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const syncCurrency = (isInitial = false) => {
        const curr = (currencySelect?.value || 'IDR').toUpperCase();
        labelCurrencies.forEach(el => {
            el.textContent = curr === 'IDR' ? '(Rp)' : '(' + curr + ')';
        });

        if (curr === 'IDR') {
            if (rateInput) {
                rateInput.value = '1';
                rateInput.readOnly = true;
            }
            if (rateHelp) rateHelp.textContent = 'Kurs 1.00 untuk IDR';
        } else {
            if (rateInput) {
                rateInput.readOnly = false;
                if (!isInitial && (rateInput.value === '1' || rateInput.value === '1,00' || !rateInput.value)) {
                    const weeklyRates = window.__weeklyRates || {};
                    if (weeklyRates[curr]) {
                        rateInput.value = Number(weeklyRates[curr]).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 4 });
                    } else if (curr === 'USD') {
                        rateInput.value = '16.000';
                    }
                }
            }
            if (rateHelp) rateHelp.textContent = 'Wajib isi kurs ke IDR (misal: 16.000)';
        }
    };

    if (currencySelect) {
        currencySelect.addEventListener('change', () => {
            syncCurrency(false);
            update();
        });
    }

    const update = () => {
        const temporary = form.querySelector('[data-cost-type]')?.value === 'temporary';
        const costInput = form.querySelector('[data-unit-cost]');
        const priceInput = form.querySelector('[data-unit-price]');
        const qtyInput = form.querySelector('[data-quantity]');
        const curr = (currencySelect?.value || 'IDR').toUpperCase();

        if (priceInput) {
            priceInput.readOnly = temporary;
            if (temporary && costInput) {
                priceInput.value = costInput.value;
            }
        }

        const quantity = parseNum(qtyInput?.value || '1');
        const unitCost = parseNum(costInput?.value || '0');
        const unitPrice = parseNum(priceInput?.value || '0');
        const rate = curr === 'IDR' ? 1 : parseNum(rateInput?.value || '1');

        const originalTotalCost = quantity * unitCost;
        const originalTotalPrice = quantity * unitPrice;
        const originalProfit = temporary ? 0 : (originalTotalPrice - originalTotalCost);

        const idrTotalCost = originalTotalCost * rate;
        const idrTotalPrice = originalTotalPrice * rate;
        const idrProfit = temporary ? 0 : (idrTotalPrice - idrTotalCost);

        const previewCost = form.querySelector('[data-preview-cost]');
        const previewTotal = form.querySelector('[data-preview-total]');
        const previewProfit = form.querySelector('[data-preview-profit]');

        if (curr === 'IDR') {
            if (previewCost) previewCost.textContent = formatIdr(idrTotalCost);
            if (previewTotal) previewTotal.textContent = formatIdr(idrTotalPrice);
            if (previewProfit) previewProfit.textContent = formatIdr(idrProfit);
        } else {
            if (previewCost) {
                previewCost.textContent = `${formatForeign(curr, originalTotalCost)} (${formatIdr(idrTotalCost)})`;
            }
            if (previewTotal) {
                previewTotal.textContent = `${formatForeign(curr, originalTotalPrice)} (${formatIdr(idrTotalPrice)})`;
            }
            if (previewProfit) {
                previewProfit.textContent = `${formatForeign(curr, originalProfit)} (${formatIdr(idrProfit)})`;
            }
        }
    };

    form.addEventListener('input', update);
    form.addEventListener('change', update);

    syncCurrency(true);
    update();
}
