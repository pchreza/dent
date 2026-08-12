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

document.querySelectorAll('.nav-link--active').forEach((link) => {
    link.setAttribute('aria-current', 'page');
});

const sidebar = document.querySelector('[data-sidebar]');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const sidebarCloseButton = document.querySelector('[data-sidebar-close]');
const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');
const navigationBreakpoint = window.matchMedia('(max-width: 1023px)');
let navigationTrigger = null;

const getFocusableElements = (container) => {
    if (!(container instanceof HTMLElement)) {
        return [];
    }

    return [...container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')]
        .filter((element) => element instanceof HTMLElement && !element.hidden && element.offsetParent !== null);
};

const isMobileNavigation = () => navigationBreakpoint.matches;

const setSidebarAccessibility = (isOpen) => {
    if (!(sidebar instanceof HTMLElement) || !(sidebarToggle instanceof HTMLButtonElement)) {
        return;
    }

    if (isMobileNavigation()) {
        sidebar.setAttribute('role', 'dialog');
        sidebar.setAttribute('aria-modal', 'true');
        sidebar.setAttribute('aria-hidden', String(!isOpen));
        sidebarToggle.setAttribute('aria-expanded', String(isOpen));
    } else {
        sidebar.removeAttribute('role');
        sidebar.removeAttribute('aria-modal');
        sidebar.removeAttribute('aria-hidden');
        sidebarToggle.setAttribute('aria-expanded', 'false');
    }
};

const openSidebar = () => {
    if (!(sidebar instanceof HTMLElement) || !isMobileNavigation()) {
        return;
    }

    navigationTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : sidebarToggle;
    sidebar.classList.add('is-open');
    sidebarBackdrop?.classList.add('is-visible');
    document.body.classList.add('is-navigation-open');
    setSidebarAccessibility(true);
    window.setTimeout(() => getFocusableElements(sidebar)[0]?.focus(), 0);
};

const closeSidebar = ({ restoreFocus = true } = {}) => {
    if (!(sidebar instanceof HTMLElement)) {
        return;
    }

    sidebar.classList.remove('is-open');
    sidebarBackdrop?.classList.remove('is-visible');
    document.body.classList.remove('is-navigation-open');
    setSidebarAccessibility(false);

    if (restoreFocus && isMobileNavigation() && navigationTrigger instanceof HTMLElement) {
        navigationTrigger.focus();
    }
};

const synchronizeNavigation = () => {
    if (!isMobileNavigation()) {
        closeSidebar({ restoreFocus: false });
        setSidebarAccessibility(false);
        return;
    }

    const isOpen = sidebar instanceof HTMLElement && sidebar.classList.contains('is-open');
    setSidebarAccessibility(isOpen);
};

if (sidebar instanceof HTMLElement && sidebarToggle instanceof HTMLButtonElement) {
    sidebarToggle.addEventListener('click', () => {
        if (sidebar.classList.contains('is-open')) {
            closeSidebar();
            return;
        }

        openSidebar();
    });

    sidebarCloseButton?.addEventListener('click', () => closeSidebar());
    sidebarBackdrop?.addEventListener('click', () => closeSidebar());

    sidebar.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', () => {
            if (isMobileNavigation()) {
                closeSidebar({ restoreFocus: false });
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const openUserMenu = document.querySelector('.user-menu[open]');
            if (openUserMenu instanceof HTMLDetailsElement) {
                openUserMenu.open = false;
                return;
            }

            if (sidebar.classList.contains('is-open')) {
                closeSidebar();
            }
            return;
        }

        if (event.key !== 'Tab' || !isMobileNavigation() || !sidebar.classList.contains('is-open')) {
            return;
        }

        const focusableElements = getFocusableElements(sidebar);
        if (focusableElements.length === 0) {
            event.preventDefault();
            return;
        }

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        const activeElement = document.activeElement;

        if (event.shiftKey && activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        } else if (!event.shiftKey && activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    });

    navigationBreakpoint.addEventListener('change', synchronizeNavigation);
    synchronizeNavigation();
}

const userMenu = document.querySelector('.user-menu');
if (userMenu instanceof HTMLDetailsElement) {
    document.addEventListener('click', (event) => {
        if (userMenu.open && event.target instanceof Node && !userMenu.contains(event.target)) {
            userMenu.open = false;
        }
    });
}
