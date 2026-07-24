import flashState from '$lib/states/flash-state.svelte';
import toastState from '$lib/states/toast-state.svelte';
import { setupProgress } from '@inertiajs/core';
import { createInertiaApp, router } from '@inertiajs/svelte';
import type { Component } from 'svelte';
import { mount } from 'svelte';
import RootApp from './RootApp.svelte';

const inertia_root = document.getElementById('app');
const pages = import.meta.glob<{ default: Component }>('./pages/**/*.svelte');

type FlashPropValue = string | string[] | null | undefined;

interface InertiaFlashProps {
    success?: FlashPropValue;
    info?: FlashPropValue;
    warning?: FlashPropValue;
    danger?: FlashPropValue;
}

function to_messages(value: FlashPropValue): string[] {
    if (Array.isArray(value)) {
        return value.filter((item): item is string => typeof item === 'string' && item.trim() !== '');
    }

    if (typeof value === 'string' && value.trim() !== '') {
        return [value];
    }

    return [];
}

router.on('navigate', (event) => {
    flashState.clear();
    toastState.clear();
});

router.on('success', (event) => {
    const pg = event.detail.page;

    if (pg.props.flash) {
        const flash = pg.props.flash as InertiaFlashProps;

        to_messages(flash.success).forEach((m) => flashState.success(m));
        to_messages(flash.danger).forEach((m) => flashState.danger(m));
        to_messages(flash.warning).forEach((m) => flashState.warning(m));
        to_messages(flash.info).forEach((m) => flashState.info(m));
    }
    else if (pg.props.errors && Object.keys(pg.props.errors).length > 0) {
        toastState.danger('Validation errors .. data submission cancelled.');
    }
});

router.on('error', (event) => {
    if (event.detail.errors) {
        const errors = event.detail.errors;
        const error = Object.entries(errors).map(([key, msg]) => msg).join(' ');
        toastState.danger(error);
    }
});

if (inertia_root) {
    void createInertiaApp({
        id: 'app',
        resolve: (name) => {
            const page = pages[`./pages/${name}.svelte`];

            if (!page) {
                throw new Error(`Unknown Inertia page: ${name}`);
            }

            return page();
        },
        setup({ el, App, props }) {
            if (!el) return;

            mount(RootApp, {
                target: el,
                props: {
                    inertiaApp: App,
                    appProps: props
                }
            });
        },
    });

    setupProgress({
        color: '#fcd34d',
        showSpinner: false,
    });
}
