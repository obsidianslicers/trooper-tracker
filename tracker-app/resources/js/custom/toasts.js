import { mount } from 'svelte';
import ToastMessages from '$lib/components/ToastMessages.svelte';
import toastState from '$lib/states/toast-state.svelte';

/**
 * Toasts on Blade pages: the same component and state the Svelte pages use (RootApp.svelte), so a
 * message looks and sits the same on both. The page's FlashType messages arrive as JSON in
 * #tt-flash (partials/messages.blade.php); plain JS shows one with toastState.success('Saved.')
 * and the like; an HTMX response sends one in X-Flash-Message (htmx-flash.js).
 */
mount(ToastMessages, { target: document.body });

/**
 * @param {Document} doc
 * @returns {Array<{ type: string, message: string }>}
 */
export function readPageFlash(doc) {
    const json = doc.getElementById('tt-flash')?.textContent;

    if (!json) {
        return [];
    }

    try {
        const flash = JSON.parse(json);

        return Array.isArray(flash) ? flash : [];
    } catch (e) {
        console.error('Error parsing the page flash messages:', e);

        return [];
    }
}

//  FlashType's values
const FLASH_TYPES = ['success', 'info', 'warning', 'danger'];

for (const { type, message } of readPageFlash(document)) {
    if (FLASH_TYPES.includes(type)) {
        toastState[type](message);
    }
}

export default toastState;
