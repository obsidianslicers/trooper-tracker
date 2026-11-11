import { svelte } from '@sveltejs/vite-plugin-svelte';
import { svelteTesting } from '@testing-library/svelte/vite';
import path from 'node:path';
import { defineConfig } from 'vitest/config';

// Kept apart from vite.config.js so unit tests don't load the Laravel build plugin.
// The Svelte plugin compiles `*.svelte.ts` view models and components; component tests opt in
// to the DOM with a `// @vitest-environment jsdom` docblock.
export default defineConfig({
    plugins: [svelte(), svelteTesting()],
    test: {
        include: ['tests/js/**/*.test.{js,ts}'],
        environment: 'node',
    },
    resolve: {
        alias: {
            '$lib': path.resolve(__dirname, './resources/svelte/lib'),
        },
    },
});
