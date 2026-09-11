export function initCustomSelects() {
    const selects = document.querySelectorAll('select[data-custom-select], .data-form select:not([data-native-select]), .filter-bar select:not([data-native-select])');

    selects.forEach((select) => {
        if (select.dataset.customSelectInitialized) return;
        select.dataset.customSelectInitialized = 'true';

        // Hide native select visually but keep accessible for form submissions & validation
        select.style.position = 'absolute';
        select.style.opacity = '0';
        select.style.pointerEvents = 'none';
        select.style.width = '1px';
        select.style.height = '1px';
        select.style.margin = '-1px';
        select.style.clip = 'rect(0, 0, 0, 0)';
        select.setAttribute('tabindex', '-1');

        // Create wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select-wrapper';

        // Insert wrapper before select and move select into wrapper
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        // Create trigger button
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'custom-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        const triggerText = document.createElement('span');
        triggerText.className = 'custom-select-text';
        
        const arrow = document.createElement('span');
        arrow.className = 'custom-select-arrow';
        arrow.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>`;

        trigger.appendChild(triggerText);
        trigger.appendChild(arrow);
        wrapper.appendChild(trigger);

        // Create dropdown menu (always positioned downwards)
        const dropdown = document.createElement('div');
        dropdown.className = 'custom-select-dropdown';

        // Check if options >= 4, add search bar
        const optionsList = Array.from(select.options);
        const hasSearch = optionsList.length >= 4;
        let searchInput = null;

        if (hasSearch) {
            const searchContainer = document.createElement('div');
            searchContainer.className = 'custom-select-search';
            
            searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Cari pilihan...';
            searchInput.autocomplete = 'off';
            searchContainer.appendChild(searchInput);
            dropdown.appendChild(searchContainer);
        }

        const optionsContainer = document.createElement('div');
        optionsContainer.className = 'custom-select-options';
        optionsContainer.setAttribute('role', 'listbox');
        dropdown.appendChild(optionsContainer);

        const noResults = document.createElement('div');
        noResults.className = 'custom-select-no-results';
        noResults.textContent = 'Tidak ada hasil ditemukan';
        noResults.style.display = 'none';
        optionsContainer.appendChild(noResults);

        wrapper.appendChild(dropdown);

        // Render options
        const renderOptions = () => {
            // Keep noResults element
            optionsContainer.innerHTML = '';
            optionsContainer.appendChild(noResults);

            Array.from(select.options).forEach((opt, idx) => {
                const optEl = document.createElement('div');
                optEl.className = 'custom-select-option';
                optEl.setAttribute('role', 'option');
                optEl.dataset.value = opt.value;
                optEl.dataset.text = opt.text;
                optEl.dataset.index = String(idx);

                const labelSpan = document.createElement('span');
                labelSpan.className = 'custom-select-option-label';
                labelSpan.textContent = opt.text;

                const checkSpan = document.createElement('span');
                checkSpan.className = 'custom-select-option-check';
                checkSpan.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="14" height="14">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>`;

                optEl.appendChild(labelSpan);
                optEl.appendChild(checkSpan);

                if (opt.selected) {
                    optEl.classList.add('is-selected');
                }

                optEl.addEventListener('click', (e) => {
                    e.stopPropagation();
                    select.value = opt.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    updateDisplay();
                    closeDropdown();
                    trigger.focus();
                });

                optionsContainer.appendChild(optEl);
            });
        };

        const updateDisplay = () => {
            const selectedOpt = select.options[select.selectedIndex];
            triggerText.textContent = selectedOpt ? selectedOpt.text : (select.getAttribute('placeholder') || 'Pilih...');
            
            // Update selected classes
            optionsContainer.querySelectorAll('.custom-select-option').forEach((el) => {
                el.classList.toggle('is-selected', el.dataset.value === select.value);
            });
        };

        const openDropdown = () => {
            // Close all other open dropdowns
            document.querySelectorAll('.custom-select-wrapper.is-open').forEach((other) => {
                if (other !== wrapper) {
                    other.classList.remove('is-open');
                    other.querySelector('.custom-select-trigger')?.setAttribute('aria-expanded', 'false');
                }
            });

            wrapper.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');

            // Ensure parent panel and filter bar allow dropdown visibility
            const panel = wrapper.closest('.panel');
            if (panel) {
                panel.style.overflow = 'visible';
            }
            const filterBar = wrapper.closest('.filter-bar');
            if (filterBar) {
                filterBar.style.position = 'relative';
                filterBar.style.zIndex = '50';
            }

            if (searchInput) {
                searchInput.value = '';
                filterOptions('');
                setTimeout(() => searchInput.focus(), 50);
            }

            // Scroll selected option into view
            const selectedEl = optionsContainer.querySelector('.custom-select-option.is-selected');
            if (selectedEl) {
                selectedEl.scrollIntoView({ block: 'nearest' });
            }
        };

        const closeDropdown = () => {
            wrapper.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');

            const panel = wrapper.closest('.panel');
            if (panel && !panel.querySelector('.custom-select-wrapper.is-open')) {
                panel.style.overflow = '';
            }
            const filterBar = wrapper.closest('.filter-bar');
            if (filterBar && !filterBar.querySelector('.custom-select-wrapper.is-open')) {
                filterBar.style.zIndex = '';
            }
        };

        const toggleDropdown = () => {
            if (wrapper.classList.contains('is-open')) {
                closeDropdown();
            } else {
                openDropdown();
            }
        };

        const filterOptions = (query) => {
            const q = query.trim().toLowerCase();
            let hasMatch = false;

            optionsContainer.querySelectorAll('.custom-select-option').forEach((el) => {
                const text = (el.dataset.text || '').toLowerCase();
                const match = text.includes(q);
                el.style.display = match ? 'flex' : 'none';
                if (match) hasMatch = true;
            });

            noResults.style.display = hasMatch ? 'none' : 'block';
        };

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                filterOptions(e.target.value);
            });

            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeDropdown();
                    trigger.focus();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const firstVisible = optionsContainer.querySelector('.custom-select-option:not([style*="display: none"])');
                    if (firstVisible) {
                        firstVisible.click();
                    }
                }
            });
        }

        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            toggleDropdown();
        });

        // Sync if select value is changed externally
        select.addEventListener('change', updateDisplay);

        // Initial setup
        renderOptions();
        updateDisplay();
    });
}

// Global click outside to close dropdowns
document.addEventListener('click', (e) => {
    if (!e.target.closest('.custom-select-wrapper')) {
        document.querySelectorAll('.custom-select-wrapper.is-open').forEach((w) => {
            w.classList.remove('is-open');
            w.querySelector('.custom-select-trigger')?.setAttribute('aria-expanded', 'false');
        });
    }
});

// Global Escape to close dropdowns
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.custom-select-wrapper.is-open').forEach((w) => {
            w.classList.remove('is-open');
            w.querySelector('.custom-select-trigger')?.setAttribute('aria-expanded', 'false');
        });
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCustomSelects);
} else {
    initCustomSelects();
}
