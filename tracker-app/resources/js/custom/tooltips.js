import { Tooltip } from 'bootstrap';

export function bindTooltips(root = document, tooltip = Tooltip) {
    root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        tooltip.getOrCreateInstance(el);
    });
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        bindTooltips();
    });
}
