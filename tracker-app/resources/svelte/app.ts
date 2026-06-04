import { setupProgress } from '@inertiajs/core';
import { createInertiaApp } from '@inertiajs/svelte';
import type { Component } from 'svelte';
import { mount } from 'svelte';
import RootApp from './RootApp.svelte';

const inertia_root = document.getElementById('app');
const pages = import.meta.glob<{ default: Component }>('./pages/**/*.svelte');

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
