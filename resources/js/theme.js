// The colour theme switch (ADR-0008): the device's setting, light or dark, remembered in this browser.
// The choice is applied before the first paint by a small inline script in the page head; this module only
// wires the buttons. Without JavaScript the buttons stay hidden and the device's setting applies.
const KEY = 'hinta-theme';
const ORDER = ['system', 'light', 'dark'];

function current() {
    const value = document.documentElement.dataset.theme;
    return value === 'light' || value === 'dark' ? value : 'system';
}

function apply(mode) {
    if (mode === 'system') {
        delete document.documentElement.dataset.theme;
    } else {
        document.documentElement.dataset.theme = mode;
    }
    try {
        if (mode === 'system') {
            localStorage.removeItem(KEY);
        } else {
            localStorage.setItem(KEY, mode);
        }
    } catch {
        // Storage may be blocked; the choice then lasts for this page only.
    }
}

function label(button) {
    const mode = current();
    button.setAttribute('aria-label', button.dataset[`label${mode[0].toUpperCase()}${mode.slice(1)}`] ?? '');
    button.dataset.mode = mode;
}

for (const button of document.querySelectorAll('[data-theme-toggle]')) {
    button.hidden = false;
    label(button);
    button.addEventListener('click', () => {
        apply(ORDER[(ORDER.indexOf(current()) + 1) % ORDER.length]);
        document.querySelectorAll('[data-theme-toggle]').forEach(label);
    });
}
