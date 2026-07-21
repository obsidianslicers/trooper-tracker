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
        rollupOptions: {
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
