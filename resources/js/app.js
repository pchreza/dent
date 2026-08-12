const treatmentItems = document.querySelector('[data-treatment-items]');
const treatmentItemTemplate = document.querySelector('#treatment-item-template');
const addTreatmentItemButton = document.querySelector('[data-treatment-item-add]');

const refreshTreatmentItemNumbers = () => {
    if (!(treatmentItems instanceof HTMLElement)) {
        return;
    }

    treatmentItems.querySelectorAll('[data-treatment-item]').forEach((item, index) => {
        const number = item.querySelector('[data-treatment-item-number]');
        const removeButton = item.querySelector('[data-treatment-item-remove]');

        if (number) {
            number.textContent = String(index + 1);
        }

        if (removeButton instanceof HTMLButtonElement) {
            removeButton.hidden = index === 0 && treatmentItems.querySelectorAll('[data-treatment-item]').length === 1;
        }
    });
};

if (
    treatmentItems instanceof HTMLElement
    && treatmentItemTemplate instanceof HTMLTemplateElement
    && addTreatmentItemButton instanceof HTMLButtonElement
) {
    const addTreatmentItem = () => {
        const nextIndex = Number(treatmentItems.dataset.nextIndex ?? '0');
        const markup = treatmentItemTemplate.innerHTML.replaceAll('__INDEX__', String(nextIndex));
        const wrapper = document.createElement('div');

        wrapper.innerHTML = markup.trim();
        const item = wrapper.firstElementChild;
        if (!item) {
            return;
        }

        treatmentItems.append(item);
        treatmentItems.dataset.nextIndex = String(nextIndex + 1);
        refreshTreatmentItemNumbers();
        item.querySelector('select, input, textarea')?.focus();
    };

    addTreatmentItemButton.addEventListener('click', addTreatmentItem);
    treatmentItems.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const removeButton = target.closest('[data-treatment-item-remove]');
        const item = target.closest('[data-treatment-item]');
        if (!removeButton || !item || treatmentItems.querySelectorAll('[data-treatment-item]').length <= 1) {
            return;
        }

        item.remove();
        refreshTreatmentItemNumbers();
    });
    refreshTreatmentItemNumbers();
}
