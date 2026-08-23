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
                            id.includes('lib')
                        ) {
                            return 'app-lib';
                        }
                        if (id.includes('pages/auth')) {
                            return 'pages-auth';
                        }
                        if (id.includes('pages/account')) {
                            return 'pages-account';
                        }
                        if (id.includes('pages/admin/troopers')) {
                            return 'pages-admin-trooper';
                        }
                        if (id.includes('pages/admin/events')) {
                            return 'pages-admin-events';
                        }
                    }
                    if (id.includes('node_modules')) {
                        // 1. Core Reactive Frameworks
                        if (id.includes('svelte') || id.includes('@inertiajs')) {
                            return 'vendor-frameworks';
                        }
                        // 2. Alpine.js ecosystem
                        if (id.includes('alpinejs')) {
                            return 'vendor-alpine';
                        }
                        // 3. Heavy Markdown Editor
                        if (id.includes('easymde') || id.includes('codemirror')) {
                            return 'vendor-editor';
                        }
                        // 4. Standalone Utilities (No jQuery/Bootstrap dependencies)
                        if (id.includes('axios') || id.includes('ziggy-js')) {
                            return 'vendor-core-utils';
                        }
                        if (id.includes('flatpickr') || id.includes('sortablejs')) {
                            return 'vendor-ui-helpers';
                        }
                        // 5. jQuery, Bootstrap, Typeahead & HTMX stay together to prevent loops
                        return 'vendor';
                    }
                },
            },
            onwarn(warning, warn) {
                const warning_id = warning.id ?? '';
                const warning_message = warning.message ?? '';

                if (
                    warning.code === 'EVAL' &&
                    (
                        warning_id.includes('htmx.org/dist/htmx.esm.js') ||
                        warning_message.includes('htmx.org/dist/htmx.esm.js')
                    )
                ) {
                    return;
                }

                warn(warning);
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
