import { svelte } from '@sveltejs/vite-plugin-svelte';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        svelte(),
        laravel({
            input: ['resources/css/app.scss',
                'resources/js/app.js',
                'resources/js/fcm-register.js',
                'resources/svelte/app.ts',],
            refresh: true,
        }),
    ],
    build: {
        sourcemap: false,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('resources/svelte')) {
                        if (
                            id.includes('lib/components') ||
                            id.includes('lib/states') ||
                            id.includes('lib/constants') ||
                            id.includes('lib/domains/types') ||
                            id.includes('lib/exceptions') ||
                            id.includes('lib/index') ||
                            id.includes('lib/logger') ||
                            id.includes('lib/utils')
                        ) {
                            return 'app-components';
                        }
                        if (id.includes('pages/auth') || id.includes('lib/domains/auth')) {
                            return 'pages-auth';
                        }
                        if (id.includes('pages/account') || id.includes('lib/domains/account')) {
                            return 'pages-account';
                        }
                        if (id.includes('pages/admin/troopers') || id.includes('lib/domains/admin/troopers')) {
                            return 'pages-admin-trooper';
                        }
                        if (id.includes('pages/admin/events') || id.includes('lib/domains/admin/events')) {
                            return 'pages-admin-events';
                        }
                        if (id.includes('pages/admin/faq')) {
                            return 'pages-admin-faq';
                        }
                        if (id.includes('pages/admin/service-records')) {
                            return 'pages-admin-service-records';
                        }
                    }
                    if (id.includes('node_modules')) {
                        // 1. Core Reactive Frameworks
                        if (id.includes('svelte') || id.includes('@inertiajs')) {
                            return 'vendor-frameworks';
                        }
                        // 2. Heavy Markdown Editor, with its own dependencies so the chunks don't loop
                        if (/node_modules\/(easymde|codemirror|codemirror-spell-checker|marked|typo-js)\//.test(id)) {
                            return 'vendor-editor';
                        }
                        // 3. Standalone Utilities (No Bootstrap dependencies)
                        if (id.includes('axios') || id.includes('ziggy-js')) {
                            return 'vendor-core-utils';
                        }
                        if (id.includes('vanilla-calendar-pro') || id.includes('sortablejs')) {
                            return 'vendor-ui-helpers';
                        }
                        // 4. Bootstrap and everything else
                        return 'vendor';
                    }
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    resolve: {
        alias: {
            '$lib': path.resolve(__dirname, './resources/svelte/lib'),
        },
    },
});
