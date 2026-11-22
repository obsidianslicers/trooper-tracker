import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';

test.use({ storageState: personas.admin.storageState });

async function pickFromModal(page, fieldLabel, search, choice) {
    await page.getByLabel(fieldLabel).click();

    const modal = page.getByRole('dialog');
    await modal.getByRole('textbox').fill(search);
    await modal.getByRole('button', { name: choice }).click();

    await expect(modal).toBeHidden();
}

test('an administrator merges a duplicate costume into the one to keep', async ({ page }) => {
    await page.goto('/admin/costumes/merge');

    await pickFromModal(page, 'Duplicate Costume', 'E2E', seeded.duplicateCostume);
    await pickFromModal(page, 'Costume to Keep', 'E2E', seeded.keptCostume);

    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('button', { name: 'Merge Costumes' }).click();

    await page.goto('/admin/costumes');
    await expect(page.getByRole('cell', { name: seeded.keptCostume })).toBeVisible();
    await expect(page.getByRole('cell', { name: seeded.duplicateCostume })).toHaveCount(0);
});

test('an administrator merges a duplicate trooper into another', async ({ page }) => {
    await page.goto('/admin/troopers/merge');

    await pickFromModal(page, 'Source Trooper', seeded.mergeSource.email, seeded.mergeSource.name);
    await pickFromModal(page, 'Target Trooper', seeded.mergeTarget.email, seeded.mergeTarget.name);

    await page.getByRole('button', { name: 'Merge Troopers' }).click();

    await expect(page.getByText('Troopers are currently being merged.')).toBeVisible();
});

// Dismissing the dashboard's alerts is covered by DashboardViewModel.test.ts: whether any are
// showing depends on what the other specs have approved by then.
test('a dashboard card opens its tool', async ({ page }) => {
    await page.goto('/admin');
    await expect(page.locator('.card').filter({ hasText: 'Costume Approvals' })).toContainText(/awaiting review|No costumes waiting/);

    await page.locator('.card').filter({ hasText: 'Reports & Stats' }).click();
    await expect(page).toHaveURL(/\/admin\/reports$/);
});
