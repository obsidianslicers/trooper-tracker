import { defineConfig } from 'vitest/config';

// Kept apart from vite.config.js so unit tests don't load the Laravel and Svelte build plugins.
export default defineConfig({
    test: {
        include: ['tests/js/**/*.test.js'],
        environment: 'node',
    },
});
