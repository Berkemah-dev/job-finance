const container = document.querySelector('[data-customer-contacts]');
if (container) {
    const addButton = container.querySelector('[data-contacts-add]');
    const rowPrototype = container.dataset.prototype ?? '';
    const rowSelector = '[data-contact-row]';

    const index = () => container.querySelectorAll(rowSelector).length;

    const refreshRemoveButtons = () => {
        container.querySelectorAll('[data-contacts-remove]').forEach((button) => {
            button.style.display = '';
        });
    };

    container.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-contacts-remove]');
        if (!remove) return;
        const row = remove.closest(rowSelector);
        row.remove();
        refreshRemoveButtons();
    });

    addButton?.addEventListener('click', () => {
        const html = rowPrototype
            .replace(/contacts\[__index__\]/g, `contacts[${index()}]`)
            .replace(/data-index="__index__"/g, `data-index="${index()}"`)
            .replace(/__index__/g, String(index()));
        container.querySelector('[data-contacts-rows]').insertAdjacentHTML('beforeend', html);
        refreshRemoveButtons();
    });

    refreshRemoveButtons();
}