import { mount } from 'svelte';
import ToastMessages from '$lib/components/ToastMessages.svelte';
import toastState from '$lib/states/toast-state.svelte';

/**
 * Toasts on Blade pages: the same component and state the Svelte pages use (RootApp.svelte), so a
 * message looks and sits the same on both. Plain JS shows one with toastState.success('Saved.')
 * and the like; an HTMX response reaches it through X-Flash-Message when the element that made
 * the request sits inside data-flash-target="toast" (htmx-flash.js).
 */
mount(ToastMessages, { target: document.body });

export default toastState;
