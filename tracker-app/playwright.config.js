import { defineConfig, devices } from '@playwright/test';
import { BASE_URL } from './tests/e2e/support/env.js';

// Browser tests run against a real app on a seeded SQLite database (tests/e2e/support/server.js).
// Specs share that database and some of them change it (approvals, merges), so they run one
// at a time in a fixed order; each spec only touches the seeded records it owns.
export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL: BASE_URL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /.*\.setup\.js/ },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
            dependencies: ['setup'],
        },
    ],
    webServer: {
        command: 'node tests/e2e/support/server.js',
        url: `${BASE_URL}/login`,
        reuseExistingServer: false,
        timeout: 120_000,
        stdout: 'ignore',
        stderr: 'pipe',
    },
});
