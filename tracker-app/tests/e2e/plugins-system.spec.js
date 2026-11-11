// Plugins, System Check, the public share page for an event, and search.
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';

function pluginRow(page, name) {
    return page.getByRole('row').filter({ hasText: name });
}

test.describe('as a system owner', () => {
    test.use({ storageState: personas.admin.storageState });

    test('configures a plugin, enables it, then disables it', async ({ page }) => {
        page.on('dialog', (dialog) => dialog.accept());
        await page.goto('/admin/plugins');
        const discord = pluginRow(page, 'Discord (discord)');
        await expect(discord).toContainText('Disabled');

        await discord.getByRole('link', { name: 'Settings' }).click();
        await page.locator('[name="settings[webhook_url]"]').fill('https://discord.example.test/api/webhooks/e2e');
        await page.getByRole('button', { name: 'Save' }).click();

        await page.goto('/admin/plugins');
        await pluginRow(page, 'Discord (discord)').getByRole('button', { name: 'Enable' }).click();
        await expect(pluginRow(page, 'Discord (discord)')).toContainText('Enabled');

        await pluginRow(page, 'Discord (discord)').getByRole('button', { name: 'Disable' }).click();
        await expect(pluginRow(page, 'Discord (discord)')).toContainText('Disabled');
    });

    test('System Check reports on PHP, the database and storage', async ({ page }) => {
        await page.goto('/admin/system-check');

        for (const check of ['PHP Version', 'Database connection', 'storage/ writable']) {
            await expect(page.getByText(check).first()).toBeVisible();
        }
    });
});

test.describe('as a moderator', () => {
    test.use({ storageState: personas.moderator.storageState });

    test('Plugins and System Check are refused', async ({ page }) => {
        for (const url of ['/admin/plugins', '/admin/system-check']) {
            const response = await page.goto(url);
            expect(response.status(), url).toBe(403);
        }
    });
});

test.describe('a shared event link', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('shows a preview card to anyone and asks them to sign in for the details', async ({ page, request }) => {
        await page.goto('/events/details/1');
        await expect(page).toHaveURL(/\/login/);

        await page.goto('/shares/event/1');
        await expect(page.getByText('This page is for members only.')).toBeVisible();
        await expect(page.locator('meta[property="og:title"]')).toHaveAttribute('content', seeded.openEvent);

        const image = await page.locator('meta[property="og:image"]').getAttribute('content');
        const response = await request.get(image);
        expect(response.status()).toBe(200);
        expect(response.headers()['content-type']).toBe('image/png');
    });
});

test.describe('search', () => {
    test.use({ storageState: personas.member.storageState });

    test('a trooper searches from the header for a trooper and an event', async ({ page }) => {
        await page.goto('/events');
        const search = page.getByRole('searchbox', { name: 'Search' }).filter({ visible: true }).first();
        await search.fill('Veteran');
        await search.press('Enter');

        await expect(page).toHaveURL(/\/search\/all\?/);
        await expect(page.getByRole('row').filter({ hasText: seeded.veteran }).first()).toBeVisible();

        await page.goto('/search/all?q=Open+Troop');
        await expect(page.getByRole('link', { name: seeded.openEvent }).first()).toBeVisible();
    });
});
