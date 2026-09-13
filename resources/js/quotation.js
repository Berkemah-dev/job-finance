const form = document.querySelector('[data-quotation-form]');
if (form) {
    const customerSelect = form.querySelector('[data-customer-select]');
    const applyContact = (picker, { name, address }) => {
        const key = picker.dataset.shipperPicker !== undefined ? 'shipper' : 'consignee';
        const nameInput = form.querySelector('[data-' + key + '-name]');
        const addrInput = form.querySelector('[data-' + key + '-address]');
        if (nameInput) nameInput.value = name ?? '';
        if (addrInput) addrInput.value = address ?? '';
    };

    // Sinkron: update payment terms langsung saat customer berubah
    const syncPaymentTerms = () => {
        const id = customerSelect?.value;
        const paymentSelect = form.querySelector('#payment_terms');
        if (!paymentSelect) return;

        if (!id) {
            paymentSelect.value = '';
            return;
        }
        
        let paymentTerms = '';
        try {
            const mapStr = form.getAttribute('data-payment-terms-map') || '{}';
            const map = JSON.parse(mapStr);
            paymentTerms = map[id] || '';
        } catch (e) {
            paymentTerms = customerSelect.options[customerSelect.selectedIndex]?.dataset?.paymentTerms || '';
        }
        
        if (!paymentTerms) {
            paymentTerms = customerSelect.options[customerSelect.selectedIndex]?.dataset?.paymentTerms || '';
        }
        
        paymentSelect.value = paymentTerms || '';
    };

    // Async: populate kontak shipper/consignee
    const populatePick = async () => {
        const id = customerSelect?.value;
        const banks = { '[data-shipper-picker]': 'shipper', '[data-consignee-picker]': 'consignee' };
        for (const [selector, type] of Object.entries(banks)) {
            const picker = form.querySelector(selector);
            if (!picker) continue;
            picker.innerHTML = '<option value="">Isi manual atau pilih kontak</option>';
            if (!id) continue;
            try {
                const response = await fetch('/api/customer-contacts/' + id, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) continue;
                const contacts = await response.json();
                contacts.filter(c => c.type === type && c.is_active !== false).forEach(c => {
                    const option = document.createElement('option');
                    option.value = c.id;
                    option.textContent = c.name + (c.company ? ' · ' + c.company : '');
                    option.dataset.name = c.name ?? '';
                    option.dataset.address = c.address ?? '';
                    picker.appendChild(option);
                });
            } catch { /* abaikan; kontak tetap bisa diisi manual */ }
        }
    };

    customerSelect?.addEventListener('change', () => {
        syncPaymentTerms();
        populatePick();
    });

    form.addEventListener('change', event => {
        const picker = event.target.closest('[data-shipper-picker], [data-consignee-picker]');
        if (!picker) return;
        const option = picker.options[picker.selectedIndex];
        applyContact(picker, { name: option?.dataset?.name, address: option?.dataset?.address });
    });

    syncPaymentTerms();
    populatePick();

    // ==========================================
    // SINGLE ITEM INPUT & ITEMS TABLE MANAGEMENT
    // ==========================================
    const singleItemPanel = document.getElementById('single-item-input-panel');
    const canManageCost = singleItemPanel?.dataset?.canManageCost === '1';
    const tbody = document.getElementById('quotation_items_tbody');
    const itemsCountDisplay = document.getElementById('items_count_display');
    const previewTotal = form.querySelector('[data-preview-total]');
    const previewProfit = form.querySelector('[data-preview-profit]');

    const inputDesc = document.getElementById('input_item_desc');
    const inputNote = document.getElementById('input_item_note');
    const inputUnit = document.getElementById('input_item_unit');
    const inputQty = document.getElementById('input_item_qty');
    const inputCurrency = document.getElementById('input_item_currency');
    const inputExchangeRate = document.getElementById('input_item_exchange_rate');
    const itemRateHint = document.getElementById('item_rate_hint');
    const labelCostCurrency = document.getElementById('label_cost_currency');
    const labelPriceCurrency = document.getElementById('label_price_currency');
    const inputCost = document.getElementById('input_item_cost');
    const inputPrice = document.getElementById('input_item_price');
    const btnSubmitItem = document.getElementById('btn_submit_single_item');
    const truckingFields = document.getElementById('trucking-pricing-fields');
    const truckingOrigin = document.getElementById('input_trucking_origin');
    const truckingDestination = document.getElementById('input_trucking_destination');
    const truckingContainer = document.getElementById('input_trucking_container_type');
    const truckingOverweight = document.getElementById('input_trucking_overweight');
    const fetchTruckingButton = document.getElementById('btn_fetch_trucking');
    const truckingStatus = document.getElementById('trucking_pricing_status');
    let truckingPricing = null;
    let truckingTimer = null;

    const syncCurrencyInputs = () => {
        const curr = (inputCurrency?.value || 'IDR').toUpperCase();
        if (labelCostCurrency) labelCostCurrency.textContent = '(' + curr + ')';
        if (labelPriceCurrency) labelPriceCurrency.textContent = '(' + curr + ')';
        if (curr === 'IDR') {
            if (inputExchangeRate) {
                inputExchangeRate.value = '1';
                inputExchangeRate.readOnly = true;
            }
            if (itemRateHint) itemRateHint.textContent = 'Kurs 1.00 untuk IDR';
        } else {
            if (inputExchangeRate) {
                inputExchangeRate.readOnly = false;
                if (inputExchangeRate.value === '1' || inputExchangeRate.value === '1,00' || !inputExchangeRate.value) {
                    inputExchangeRate.value = '16.000';
                }
            }
            if (itemRateHint) itemRateHint.textContent = 'Wajib isi kurs ke IDR (misal: 16.000)';
        }
    };

    inputCurrency?.addEventListener('change', syncCurrencyInputs);
    syncCurrencyInputs();

    const isTrucking = () => (inputDesc?.value || '').trim().toUpperCase() === 'TRUCKING';
    const setTruckingStatus = (message, color = '#64748b') => {
        if (truckingStatus) { truckingStatus.textContent = message; truckingStatus.style.color = color; }
    };
    const syncTruckingFields = () => {
        const active = isTrucking();
        if (truckingFields) truckingFields.hidden = !active;
        if (!active) { truckingPricing = null; setTruckingStatus('Isi asal, tujuan, dan tipe armada. Harga akan dicari otomatis.'); }
        else if (truckingOrigin?.value && truckingDestination?.value) scheduleTruckingLookup();
    };
    const fetchTruckingPrice = async () => {
        if (!isTrucking() || !truckingOrigin?.value.trim() || !truckingDestination?.value.trim()) return;
        const endpoint = form.dataset.pricingEndpoint;
        if (!endpoint) return;
        setTruckingStatus('Mencari tarif dari Master Trucking...', '#1d4ed8');
        if (fetchTruckingButton) fetchTruckingButton.disabled = true;
        try {
            const params = new URLSearchParams({
                port_origin: truckingOrigin.value.trim(),
                destination: truckingDestination.value.trim(),
                container_type: truckingContainer.value,
                overweight: truckingOverweight.value,
            });
            const response = await fetch(`${endpoint}?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            if (!response.ok || !data.found) {
                truckingPricing = null;
                if (inputCost) inputCost.value = '0';
                inputPrice.value = '0';
                setTruckingStatus('Tarif belum tersedia untuk rute, armada, dan kategori ini.', '#b45309');
                return;
            }
            truckingPricing = data;
            if (inputCost) inputCost.value = data.unit_cost ?? 0;
            inputPrice.value = data.unit_price ?? 0;
            if (inputCurrency && data.currency) {
                inputCurrency.value = data.currency;
                syncCurrencyInputs();
            }
            if (inputExchangeRate && data.exchange_rate) {
                inputExchangeRate.value = data.exchange_rate;
            }
            setTruckingStatus(`Tarif ditemukan: ${data.port_origin} → ${data.destination}. Modal dan harga jual sudah diisi.`, '#15803d');
        } catch (error) {
            truckingPricing = null;
            setTruckingStatus('Tarif belum bisa diambil. Periksa koneksi atau Master Trucking.', '#b91c1c');
        } finally { if (fetchTruckingButton) fetchTruckingButton.disabled = false; }
    };
    function scheduleTruckingLookup() {
        clearTimeout(truckingTimer);
        truckingTimer = setTimeout(fetchTruckingPrice, 350);
    }
    [truckingOrigin, truckingDestination].forEach(input => input?.addEventListener('input', scheduleTruckingLookup));
    [truckingContainer, truckingOverweight].forEach(input => input?.addEventListener('change', scheduleTruckingLookup));
    fetchTruckingButton?.addEventListener('click', fetchTruckingPrice);
    inputDesc?.addEventListener('change', syncTruckingFields);
    syncTruckingFields();

    let itemsArray = [];

    const parseFormattedNumber = value => {
        const raw = String(value ?? '').trim().replace(/\s/g, '');
        if (!raw) return 0;
        if (raw.includes('.') && raw.includes(',')) return Number(raw.replace(/\./g, '').replace(',', '.')) || 0;
        if (raw.match(/^\d{1,3}(\.\d{3})+$/)) return Number(raw.replace(/\./g, '')) || 0;
        if (raw.includes('.')) return Number(raw) || 0;
        return Number(raw.replace(',', '.')) || 0;
    };
    const formatInputNumber = input => {
        if (!input || input.value === '') return;
        const value = parseFormattedNumber(input.value);
        input.value = Number.isInteger(value) ? value.toLocaleString('id-ID') : value.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    };
    [inputCost, inputPrice, inputQty, inputExchangeRate].forEach(input => {
        input?.addEventListener('blur', () => formatInputNumber(input));
        input?.addEventListener('focus', () => { input.value = input.value.replace(/\./g, '').replace(',', '.'); });
    });

    const formatMoney = num => {
        const val = Number(num) || 0;
        return 'Rp ' + val.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const renderTable = () => {
        if (!tbody) return;
        tbody.innerHTML = '';
        const colspan = canManageCost ? 9 : 8;

        if (itemsArray.length === 0) {
            const emptyTr = document.createElement('tr');
            emptyTr.innerHTML = `<td colspan="${colspan}" style="text-align: center; color: #94a3b8; padding: 28px 16px;">Belum ada item ditambahkan. Silakan isi form di atas dan klik <strong>"+ Tambah Item ke Daftar"</strong>.</td>`;
            tbody.appendChild(emptyTr);
            if (itemsCountDisplay) itemsCountDisplay.textContent = '0 item';
            if (previewTotal) previewTotal.textContent = 'Rp 0,00';
            if (previewProfit) previewProfit.textContent = 'Rp 0,00';
            return;
        }

        let grandTotalIdr = 0;
        let grandProfitIdr = 0;

        itemsArray.forEach((item, index) => {
            const qty = parseFormattedNumber(item.quantity) || 1;
            const cost = parseFormattedNumber(item.unit_cost) || 0;
            const price = parseFormattedNumber(item.unit_price) || 0;
            const rate = parseFormattedNumber(item.exchange_rate) || 1;
            const curr = item.currency || 'IDR';

            const costTotalIdr = qty * cost * rate;
            const sellTotalIdr = qty * price * rate;

            grandTotalIdr += sellTotalIdr;
            grandProfitIdr += (sellTotalIdr - costTotalIdr);

            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #e2e8f0';

            const costColumnHtml = canManageCost ? `
                <td style="text-align: right; padding: 10px 8px; color: #64748b;">
                    ${curr !== 'IDR' ? curr + ' ' + cost.toLocaleString('id-ID', { maximumFractionDigits: 2 }) : formatMoney(cost)}
                    <input type="hidden" name="items[${index}][unit_cost]" value="${item.unit_cost}">
                </td>
            ` : `<input type="hidden" name="items[${index}][unit_cost]" value="${item.unit_cost || 0}">`;

            tr.innerHTML = `
                <td style="text-align: center; padding: 10px 8px; color: #64748b; font-weight: 600;">${index + 1}</td>
                <td style="padding: 10px 12px;">
                    <strong style="color: #0f172a;">${escapeHtml(item.description)}</strong>
                    ${item.note ? `<div class="muted-cell" style="font-size: 11px; color: #64748b; margin-top: 2px;">${escapeHtml(item.note)}</div>` : ''}
                    <input type="hidden" name="items[${index}][description]" value="${escapeHtml(item.description)}">
                    <input type="hidden" name="items[${index}][note]" value="${escapeHtml(item.note || '')}">
                    <input type="hidden" name="items[${index}][type]" value="${escapeHtml(item.type || 'provision')}">
                    <input type="hidden" name="items[${index}][pricing_source]" value="${escapeHtml(item.pricing_source || 'manual')}">
                    <input type="hidden" name="items[${index}][pricing_id]" value="${escapeHtml(item.pricing_id || '')}">
                    <input type="hidden" name="items[${index}][currency]" value="${escapeHtml(curr)}">
                    <input type="hidden" name="items[${index}][exchange_rate]" value="${escapeHtml(item.exchange_rate || '1.00')}">
                    <input type="hidden" name="items[${index}][pricing_snapshot]" value="${escapeHtml(typeof item.pricing_snapshot === 'object' ? JSON.stringify(item.pricing_snapshot) : (item.pricing_snapshot || ''))}">
                    <input type="hidden" name="items[${index}][container_type]" value="${escapeHtml(item.container_type || '')}">
                    <input type="hidden" name="items[${index}][overweight]" value="${item.overweight ? '1' : '0'}">
                    <input type="hidden" name="items[${index}][port_origin]" value="${escapeHtml(item.port_origin || '')}">
                    <input type="hidden" name="items[${index}][destination]" value="${escapeHtml(item.destination || '')}">
                </td>
                <td style="padding: 10px 8px;">
                    <span class="status-badge" style="background:${curr === 'IDR' ? '#f1f5f9' : '#e0e7ff'}; color:${curr === 'IDR' ? '#334155' : '#3730a3'}; font-size:11px; font-weight:700;">${curr}</span>
                    @if(true)
                    ${curr !== 'IDR' ? `<div style="font-size:10.5px;color:#64748b;margin-top:2px;">@ ${rate.toLocaleString('id-ID')}</div>` : ''}
                    @endif
                </td>
                <td style="padding: 10px 8px;">
                    <span class="status-badge" style="background:#f1f5f9; color:#334155; font-size:11px;">${escapeHtml(item.unit || 'Shipment')}</span>
                    <input type="hidden" name="items[${index}][unit]" value="${escapeHtml(item.unit || 'Shipment')}">
                </td>
                <td style="text-align: right; padding: 10px 8px; font-weight: 600;">
                    ${Number(item.quantity).toLocaleString('id-ID', { maximumFractionDigits: 2 })}
                    <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
                </td>
                ${costColumnHtml}
                <td style="text-align: right; padding: 10px 8px; font-weight: 600; color: #1e3a8a;">
                    ${curr !== 'IDR' ? curr + ' ' + price.toLocaleString('id-ID', { maximumFractionDigits: 2 }) : formatMoney(price)}
                    <input type="hidden" name="items[${index}][unit_price]" value="${item.unit_price}">
                </td>
                <td style="text-align: right; padding: 10px 12px; font-weight: 700; color: #0f172a;">
                    ${formatMoney(sellTotalIdr)}
                </td>
                <td style="text-align: center; padding: 10px 8px;">
                    <div style="display: flex; gap: 4px; justify-content: center;">
                        <button type="button" class="button button-secondary button-sm btn-edit-item" data-index="${index}" style="padding: 3px 8px; font-size: 11px;">Edit</button>
                        <button type="button" class="button button-danger button-sm btn-delete-item" data-index="${index}" style="padding: 3px 8px; font-size: 11px;">Hapus</button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });

        if (itemsCountDisplay) itemsCountDisplay.textContent = itemsArray.length + ' item ditambahkan';
        if (previewTotal) previewTotal.textContent = formatMoney(grandTotalIdr);
        if (previewProfit) previewProfit.textContent = formatMoney(grandProfitIdr);
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Add item function
    const addItem = (item) => {
        itemsArray.push({
            description: item.description || '',
            note: item.note || '',
            unit: item.unit || 'Shipment',
            quantity: item.quantity || '1',
            unit_cost: item.unit_cost === undefined || item.unit_cost === null || item.unit_cost === '' ? '0' : String(item.unit_cost),
            unit_price: item.unit_price === undefined || item.unit_price === null || item.unit_price === '' ? '0' : String(item.unit_price),
            type: item.type || 'provision',
            pricing_source: item.pricing_source || 'manual',
            pricing_id: item.pricing_id || '',
            currency: item.currency || 'IDR',
            exchange_rate: item.exchange_rate || '1.00',
            pricing_snapshot: item.pricing_snapshot || null,
            container_type: item.container_type || '',
            overweight: !!item.overweight,
            port_origin: item.port_origin || '',
            destination: item.destination || '',
        });
        renderTable();
    };

    window.addQuotationItem = addItem;

    // Handle submit single item button
    btnSubmitItem?.addEventListener('click', () => {
        const desc = inputDesc?.value?.trim();
        const qty = parseFormattedNumber(inputQty?.value || 0);
        const price = parseFormattedNumber(inputPrice?.value || 0);
        const cost = parseFormattedNumber(inputCost?.value || 0);
        const curr = (inputCurrency?.value || 'IDR').toUpperCase();
        const rate = curr === 'IDR' ? 1 : (parseFormattedNumber(inputExchangeRate?.value || 0));
        const trucking = isTrucking();

        if (!desc) {
            alert('Pilih atau isi Uraian Biaya terlebih dahulu.');
            inputDesc?.focus();
            return;
        }

        if (isNaN(qty) || qty <= 0) {
            alert('Jumlah (Qty) harus lebih dari 0.');
            inputQty?.focus();
            return;
        }

        if (curr !== 'IDR' && (!rate || rate <= 0)) {
            alert('Wajib mengisi Kurs untuk mata uang ' + curr + '.');
            inputExchangeRate?.focus();
            return;
        }

        if (trucking && !truckingPricing) {
            fetchTruckingPrice();
            alert('Tarif trucking belum ditemukan. Isi asal dan tujuan, lalu tunggu harga muncul.');
            return;
        }

        addItem({
            description: desc,
            note: inputNote?.value?.trim() || '',
            unit: inputUnit?.value || 'Shipment',
            quantity: String(qty),
            unit_cost: String(cost),
            unit_price: String(price),
            type: 'provision',
            pricing_source: trucking ? 'trucking' : 'manual',
            pricing_id: trucking ? (truckingPricing?.pricing_id || '') : '',
            pricing_snapshot: trucking ? (truckingPricing?.snapshot || truckingPricing) : null,
            currency: curr,
            exchange_rate: String(rate),
            container_type: trucking ? truckingContainer.value : '',
            overweight: trucking ? truckingOverweight.value === '1' : false,
            port_origin: trucking ? truckingOrigin.value.trim() : '',
            destination: trucking ? truckingDestination.value.trim() : '',
        });

        // Reset inputs
        if (inputDesc) inputDesc.value = '';
        if (inputNote) inputNote.value = '';
        if (inputUnit) { inputUnit.value = 'Shipment'; inputUnit.dispatchEvent(new Event('change', { bubbles: true })); }
        if (inputQty) inputQty.value = '1';
        if (inputCost) inputCost.value = '0';
        if (inputPrice) inputPrice.value = '0';
        if (inputCurrency) { inputCurrency.value = 'IDR'; syncCurrencyInputs(); }
        if (truckingOrigin) truckingOrigin.value = '';
        if (truckingDestination) truckingDestination.value = '';
        truckingPricing = null;
        syncTruckingFields();
        inputDesc?.focus();
    });

    // Quick charge selection chips
    document.querySelectorAll('.btn-quick-charge').forEach(btn => {
        btn.addEventListener('click', () => {
            if (inputDesc) {
                const value = btn.dataset.charge;
                if (inputDesc.tagName === 'SELECT' && ![...inputDesc.options].some(option => option.value === value)) {
                    inputDesc.add(new Option(value, value));
                }
                inputDesc.value = value;
                inputDesc.dispatchEvent(new Event('change', { bubbles: true }));
                inputPrice?.focus();
            }
        });
    });

    // Support Enter key on price & cost input to submit item
    [inputPrice, inputCost, inputQty, inputNote, inputExchangeRate].forEach(inp => {
        inp?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnSubmitItem?.click();
            }
        });
    });

    // Handle Table Actions (Edit & Hapus)
    tbody?.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('.btn-delete-item');
        if (deleteBtn) {
            const index = parseInt(deleteBtn.dataset.index, 10);
            if (!isNaN(index) && itemsArray[index]) {
                itemsArray.splice(index, 1);
                renderTable();
            }
            return;
        }

        const editBtn = e.target.closest('.btn-edit-item');
        if (editBtn) {
            const index = parseInt(editBtn.dataset.index, 10);
            if (!isNaN(index) && itemsArray[index]) {
                const item = itemsArray[index];
                if (inputDesc) inputDesc.value = item.description || '';
                if (inputNote) inputNote.value = item.note || '';
                if (inputUnit) inputUnit.value = item.unit || 'Shipment';
                if (inputQty) inputQty.value = item.quantity || '1';
                if (inputCurrency) {
                    inputCurrency.value = item.currency || 'IDR';
                    syncCurrencyInputs();
                }
                if (inputExchangeRate) inputExchangeRate.value = item.exchange_rate || '1';
                if (inputCost) inputCost.value = item.unit_cost || '0';
                if (inputPrice) inputPrice.value = item.unit_price || '0';
                if (truckingOrigin) truckingOrigin.value = item.port_origin || '';
                if (truckingDestination) truckingDestination.value = item.destination || '';
                if (truckingContainer) truckingContainer.value = item.container_type || truckingContainer.value;
                if (truckingOverweight) truckingOverweight.value = item.overweight ? '1' : '0';
                truckingPricing = item.pricing_source === 'trucking' ? (item.pricing_snapshot || null) : null;
                syncTruckingFields();
                
                itemsArray.splice(index, 1);
                renderTable();

                const panel = document.getElementById('single-item-input-panel');
                panel?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                inputDesc?.focus();
            }
        }
    });

    // Load initial items from JSON script tag
    try {
        const scriptTag = document.getElementById('initial-items-data');
        if (scriptTag && scriptTag.textContent.trim()) {
            const parsed = JSON.parse(scriptTag.textContent.trim());
            if (Array.isArray(parsed) && parsed.length > 0) {
                itemsArray = parsed;
            }
        }
    } catch (err) {
        console.error('Error parsing initial items:', err);
    }

    renderTable();

    // Prevent submitting without at least 1 item
    form.addEventListener('submit', (e) => {
        if (itemsArray.length === 0) {
            e.preventDefault();
            alert('Silakan tambahkan setidaknya 1 item biaya ke daftar penawaran sebelum menyimpan.');
            inputDesc?.focus();
        }
    });
}
