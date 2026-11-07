import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';

test.describe('membership approvals', () => {
    test.use({ storageState: personas.moderator.storageState });

    test('a moderator approves a pending recruit in their club', async ({ page }) => {
        await page.goto('/admin/troopers/approvals/index');

        const card = page.locator('.card').filter({ hasText: seeded.recruit.name });
        await expect(card).toBeVisible();

        await card.getByRole('button', { name: 'Approve' }).click();

        await expect(page.getByText('Trooper membership approved successfully.')).toBeVisible();

        await page.reload();
        await expect(page.getByText(seeded.recruit.name)).toHaveCount(0);
    });
});

test.describe('permission approvals', () => {
    test.use({ storageState: personas.admin.storageState });

    test('an administrator opens the permission approvals queue', async ({ page }) => {
        await page.goto('/admin/troopers/permissions/approvals');

        await expect(page).toHaveTitle(/Permission/i);
    });
});
