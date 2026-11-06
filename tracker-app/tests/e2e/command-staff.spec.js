import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';

test.use({ storageState: personas.admin.storageState });

async function pickFromModal(page, fieldLabel, search, choice) {
    await page.getByLabel(fieldLabel).click();

    const modal = page.locator('.modal.show');
    await modal.getByLabel('Search').fill(search);
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
    await expect(page.getByText(seeded.keptCostume)).toBeVisible();
    await expect(page.getByText(seeded.duplicateCostume)).toHaveCount(0);
});

test('an administrator merges a duplicate trooper into another', async ({ page }) => {
    await page.goto('/admin/troopers/merge');

    await pickFromModal(page, 'Source Trooper', seeded.mergeSource.email, seeded.mergeSource.name);
    await pickFromModal(page, 'Target Trooper', seeded.mergeTarget.email, seeded.mergeTarget.name);

    await page.getByRole('button', { name: 'Merge Troopers' }).click();

    await expect(page.getByText(/merged/i).first()).toBeVisible();
});
