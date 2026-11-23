import toastState from '$lib/states/toast-state.svelte';
import { setupProgress } from '@inertiajs/core';
import { createInertiaApp, router } from '@inertiajs/svelte';
import 'bootstrap';
import { Collapse } from 'bootstrap';
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

function handleMessages(event: CustomEvent): void {

    //  ie. form submission success, or page navigation success
    const pg = event.detail.page;

    const flash = (pg.props.flash ?? {}) as InertiaFlashProps;
    const shown = [
        ...to_messages(flash.success).map((m) => toastState.success(m)),
        ...to_messages(flash.danger).map((m) => toastState.danger(m)),
        ...to_messages(flash.warning).map((m) => toastState.warning(m)),
        ...to_messages(flash.info).map((m) => toastState.info(m)),
    ];

    if (shown.length === 0 && pg.props.errors && Object.keys(pg.props.errors).length > 0) {
        toastState.danger('Validation errors .. data submission cancelled.');
    }
}

router.on('navigate', (event) => {
    const openNavbars = document.querySelectorAll('.navbar-collapse.show');
    openNavbars.forEach(nav => {
        const collapseInstance = Collapse.getInstance(nav);
        if (collapseInstance) {
            collapseInstance.hide();
        }
    });

    handleMessages(event);
});

router.on('success', (event) => {
    handleMessages(event);
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
            const splash = document.getElementById('app-loading');

            if (splash) {
                splash.remove();
            }

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
