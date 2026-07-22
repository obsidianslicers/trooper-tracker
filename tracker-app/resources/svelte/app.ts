import toastState from '$lib/states/toast-state.svelte';
import { setupProgress } from '@inertiajs/core';
import { createInertiaApp, router } from '@inertiajs/svelte';
import type { Component } from 'svelte';
import { mount } from 'svelte';
import RootApp from './RootApp.svelte';

const inertia_root = document.getElementById('app');
const pages = import.meta.glob<{ default: Component }>('./pages/**/*.svelte');

router.on('navigate', (event) => {
    toastState.clear();
});

router.on('success', (event) => {
    const pg = event.detail.page;

    if (pg.props.flash) {
        const flash = pg.props.flash as { success?: string, danger?: string, warning?: string, info?: string };

        if (flash.success) {
            toastState.success(flash.success);
        }
        else if (flash.danger) {
            toastState.danger(flash.danger);
        }
        else if (flash.warning) {
            toastState.warning(flash.warning);
        }
        else if (flash.info) {
            toastState.info(flash.info);
        }
    }
    else if (pg.props.errors && Object.keys(pg.props.errors).length > 0) {
        toastState.danger('Validation errors .. data submission cancelled.');
    }
});

router.on('error', (event) => {
    if (event.detail.errors) {
        const errors = event.detail.errors;
        const error = Object.entries(errors).map(([key, msg]) => `${key}: ${msg}`).join(', ');
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
