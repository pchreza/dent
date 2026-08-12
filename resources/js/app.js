const journeyPayload = document.querySelector('[data-dental-journeys]');
const jawToothButtons = [...document.querySelectorAll('[data-jaw-tooth]')];
const journeyTitle = document.querySelector('[data-journey-title]');
const journeyCode = document.querySelector('[data-journey-code]');
const journeyStatus = document.querySelector('[data-journey-status]');
const journeySurfaces = document.querySelector('[data-journey-surfaces]');
const journeyNote = document.querySelector('[data-journey-note]');
const journeyTreatments = document.querySelector('[data-journey-treatments]');
const journeyTimeline = document.querySelector('[data-journey-timeline]');
const dentalToothInput = document.querySelector('[data-dental-tooth-input]');
const dentalSurfaceInput = document.querySelector('[data-dental-surface-input]');
const dentalEntryLabel = document.querySelector('[data-dental-entry-label]');

const treatmentSteps = [
    ['planned', 'برنامه‌ریزی'],
    ['approved', 'تأیید'],
    ['in_progress', 'در حال انجام'],
    ['completed', 'تکمیل'],
];

const createElement = (tag, className, text) => {
    const element = document.createElement(tag);
    if (className) {
        element.className = className;
    }
    if (text !== undefined) {
        element.textContent = text;
    }
    return element;
};

const formatDateTime = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

let journeys = {};
if (journeyPayload?.textContent) {
    try {
        journeys = JSON.parse(journeyPayload.textContent);
    } catch {
        journeys = {};
    }
}

const setUrlSelection = (toothCode, surfaceCode) => {
    const url = new URL(window.location.href);
    url.searchParams.set('tooth', toothCode);
    if (surfaceCode) {
        url.searchParams.set('surface', surfaceCode);
    }
    window.history.replaceState({}, '', url);
};

const renderSurfaces = (journey, selectedSurface) => {
    if (!(journeySurfaces instanceof HTMLElement)) {
        return;
    }

    journeySurfaces.replaceChildren();
    const entries = Array.isArray(journey?.clinicalEntries) ? journey.clinicalEntries : [];
    const uniqueSurfaces = [...new Map(entries.map((entry) => [entry.surface_code, entry.surface])).entries()];

    if (uniqueSurfaces.length === 0) {
        journeySurfaces.append(createElement('span', 'journey-surface-chip', 'کل دندان'));
        return;
    }

    uniqueSurfaces.forEach(([surfaceCode, surfaceLabel]) => {
        const button = createElement('button', `journey-surface-chip${surfaceCode === selectedSurface ? ' is-active' : ''}`, surfaceLabel);
        button.type = 'button';
        button.dataset.journeySurface = surfaceCode;
        journeySurfaces.append(button);
    });
};

const renderTreatments = (journey, toothCode, surfaceCode) => {
    if (!(journeyTreatments instanceof HTMLElement)) {
        return;
    }

    journeyTreatments.replaceChildren();
    const treatments = Array.isArray(journey?.treatments) ? journey.treatments : [];

    if (treatments.length === 0) {
        const empty = createElement('div', 'journey-empty-action');
        empty.append(createElement('p', '', 'هنوز طرح درمانی برای این دندان ثبت نشده است.'));
        const url = new URL(window.location.href);
        const patientMatch = url.pathname.match(/patients\/(\d+)/);
        if (patientMatch) {
            const link = createElement('a', 'button button--secondary', 'افزودن به طرح درمان');
            link.href = `/clinic/patients/${patientMatch[1]}/treatment-plans/create?tooth=${encodeURIComponent(toothCode)}&surface=${encodeURIComponent(surfaceCode)}`;
            empty.append(link);
        }
        journeyTreatments.append(empty);
        return;
    }

    treatments.forEach((treatment) => {
        const card = createElement('article', 'treatment-path-card');
        const heading = createElement('div');
        heading.append(createElement('strong', '', treatment.stage));
        heading.append(createElement('span', '', `${treatment.treatment} · ${treatment.plan_title}`));
        card.append(heading);

        const stepper = createElement('ol', 'treatment-stepper');
        const currentIndex = treatmentSteps.findIndex(([status]) => status === treatment.status);
        treatmentSteps.forEach(([status, label], index) => {
            const state = treatment.status === 'cancelled'
                ? ' is-cancelled'
                : (index <= currentIndex ? ' is-complete' : '');
            stepper.append(createElement('li', state.trim(), label));
        });
        card.append(stepper);

        const meta = createElement('div', 'treatment-path-card__meta');
        meta.append(createElement('span', '', treatment.status_label));
        if (treatment.planned_on) {
            const time = createElement('time', '', formatDateTime(treatment.planned_on));
            time.dir = 'ltr';
            meta.append(time);
        }
        card.append(meta);
        journeyTreatments.append(card);
    });
};

const renderTimeline = (journey) => {
    if (!(journeyTimeline instanceof HTMLElement)) {
        return;
    }

    journeyTimeline.replaceChildren();
    const timeline = Array.isArray(journey?.timeline) ? journey.timeline : [];

    if (timeline.length === 0) {
        journeyTimeline.append(createElement('li', 'journey-timeline__empty', 'هنوز رویدادی برای این دندان ثبت نشده است.'));
        return;
    }

    timeline.forEach((event) => {
        const item = createElement('li', `journey-timeline__event journey-timeline__event--${event.type}`);
        const content = createElement('div');
        content.append(createElement('strong', '', event.title));
        content.append(createElement('span', '', `${event.type_label} · ${event.subtitle}`));
        if (event.note) {
            content.append(createElement('p', '', event.note));
        }
        const time = createElement('time', '', formatDateTime(event.at));
        time.dir = 'ltr';
        item.append(content, time);
        journeyTimeline.append(item);
    });
};

const selectJawTooth = (button, surfaceCode = null) => {
    const toothCode = button.dataset.toothCode;
    if (!toothCode) {
        return;
    }

    const journey = journeys[toothCode] ?? {};
    const resolvedSurface = surfaceCode ?? button.dataset.toothSurface ?? 'all';
    jawToothButtons.forEach((toothButton) => {
        const selected = toothButton === button;
        toothButton.classList.toggle('is-selected', selected);
        toothButton.setAttribute('aria-pressed', String(selected));
    });

    if (journeyTitle) {
        journeyTitle.textContent = button.dataset.toothName ?? `دندان ${toothCode}`;
    }
    if (journeyCode) {
        journeyCode.textContent = `FDI ${toothCode}`;
    }
    if (journeyStatus) {
        journeyStatus.textContent = button.dataset.toothStatus ?? 'بدون وضعیت ثبت‌شده';
    }
    if (journeyNote) {
        const latestEntry = Array.isArray(journey.clinicalEntries) ? journey.clinicalEntries[0] : null;
        journeyNote.textContent = latestEntry?.note ?? '';
        journeyNote.hidden = ! latestEntry?.note;
    }
    if (dentalToothInput instanceof HTMLInputElement) {
        dentalToothInput.value = toothCode;
    }
    if (dentalSurfaceInput instanceof HTMLInputElement) {
        dentalSurfaceInput.value = resolvedSurface;
    }
    if (dentalEntryLabel) {
        dentalEntryLabel.textContent = `وضعیت جدید برای ${button.dataset.toothName ?? `دندان ${toothCode}`}`;
    }

    renderSurfaces(journey, resolvedSurface);
    renderTreatments(journey, toothCode, resolvedSurface);
    renderTimeline(journey);
    setUrlSelection(toothCode, resolvedSurface);
};

if (jawToothButtons.length > 0) {
    jawToothButtons.forEach((button) => {
        button.addEventListener('click', () => selectJawTooth(button));
    });

    journeySurfaces?.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }
        const surfaceButton = target.closest('[data-journey-surface]');
        const selectedButton = jawToothButtons.find((button) => button.getAttribute('aria-pressed') === 'true');
        if (surfaceButton instanceof HTMLElement && selectedButton) {
            selectJawTooth(selectedButton, surfaceButton.dataset.journeySurface ?? 'all');
        }
    });
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
