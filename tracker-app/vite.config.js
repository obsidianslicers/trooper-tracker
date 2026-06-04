import { svelte } from '@sveltejs/vite-plugin-svelte';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        svelte(),
        laravel({
            input: [
                'resources/css/app.scss',
                'resources/js/app.js',
                'resources/js/fcm-register.js',
                'resources/svelte/app.ts',
            ],
            refresh: true,
        }),
    ],
});
