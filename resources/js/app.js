const toothInput = document.querySelector('[data-dental-tooth-input]');
const toothButtons = [...document.querySelectorAll('[data-dental-tooth]')];
const selectedTooth = document.querySelector('[data-dental-selected-tooth]');
const selectedTitle = document.querySelector('[data-dental-selected-title]');
const selectedStatus = document.querySelector('[data-dental-selected-status]');

const selectDentalTooth = (button, shouldFocusForm = false) => {
    const toothCode = button.getAttribute('data-dental-tooth');
    const statusLabel = button.getAttribute('data-dental-status-label') ?? 'بدون وضعیت ثبت‌شده';

    if (!toothCode) {
        return;
    }

    toothButtons.forEach((toothButton) => {
        toothButton.setAttribute('aria-pressed', String(toothButton === button));
    });

    if (toothInput instanceof HTMLSelectElement) {
        toothInput.value = toothCode;
    }

    if (selectedTooth) {
        selectedTooth.textContent = toothCode;
    }
    if (selectedTitle) {
        selectedTitle.textContent = `دندان ${toothCode} انتخاب شد`;
    }
    if (selectedStatus) {
        selectedStatus.textContent = `آخرین وضعیت: ${statusLabel}`;
    }

    if (shouldFocusForm && toothInput instanceof HTMLSelectElement) {
        toothInput.focus();
        if (window.matchMedia('(max-width: 1040px)').matches) {
            document.querySelector('#chart-entry')?.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }
    }
};

if (toothInput instanceof HTMLSelectElement) {
    toothButtons.forEach((button) => {
        button.addEventListener('click', () => selectDentalTooth(button, true));
    });

    toothInput.addEventListener('change', () => {
        const matchingButton = toothButtons.find((button) => button.getAttribute('data-dental-tooth') === toothInput.value);
        if (matchingButton) {
            selectDentalTooth(matchingButton);
        }
    });

    const initialButton = toothButtons.find((button) => button.getAttribute('data-dental-tooth') === toothInput.value);
    if (initialButton) {
        selectDentalTooth(initialButton);
    }
}

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
