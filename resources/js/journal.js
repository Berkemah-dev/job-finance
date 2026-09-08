const form = document.querySelector('[data-journal-form]');
if (form) {
    const list = form.querySelector('[data-entry-list]');
    const template = form.querySelector('[data-entry-template]');
    let index = 0;
    const update = () => {
        const sum = (name) => [...list.querySelectorAll(`[data-name="${name}"]`)].reduce((total, input) => total + Number(input.value || 0), 0);
        form.querySelector('[data-total-debit]').textContent = `Rp ${sum('debit').toLocaleString('id-ID', { minimumFractionDigits: 2 })}`;
        form.querySelector('[data-total-credit]').textContent = `Rp ${sum('credit').toLocaleString('id-ID', { minimumFractionDigits: 2 })}`;
    };
    const add = () => {
        const row = template.content.firstElementChild.cloneNode(true);
        row.querySelectorAll('[data-name]').forEach((input) => { input.name = `entries[${index}][${input.dataset.name}]`; });
        row.querySelector('[data-remove-entry]').addEventListener('click', () => { if (list.children.length > 2) row.remove(); update(); });
        row.addEventListener('input', update);
        list.append(row); index += 1;
    };
    form.querySelector('[data-add-entry]').addEventListener('click', add);
    add(); add();
}
