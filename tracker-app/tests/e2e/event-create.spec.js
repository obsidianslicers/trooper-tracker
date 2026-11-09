import { test, expect } from '@playwright/test';
import { personas } from './support/personas.js';

test.use({ storageState: personas.admin.storageState });

async function openSection(page, label) {
    await page.getByRole('button', { name: label }).click();
}

test('a create that fails validation keeps what was entered', async ({ page }) => {
    await page.goto('/admin/events/create');

    await page.locator('#picker-container-organization_id').click();
    const modal = page.locator('#modal-picker');
    await modal.locator('li[data-name="501st Legion"]').click();
    await expect(modal).toBeHidden();

    await page.getByText('Input Manually').click();

    // The Yes/No fields open on the server's defaults.
    await expect(page.locator('#tentative_signups_allowed')).toHaveValue('1');
    await expect(page.locator('#require_mission_brief_ack')).toHaveValue('0');

    // Leave the name blank so the submit fails, and fill in the rest.
    await openSection(page, 'Schedule');
    await page.locator('#event_start').evaluate((el) => el._flatpickr.setDate(new Date(2030, 5, 1, 10, 0), true));
    await page.locator('#event_end').evaluate((el) => el._flatpickr.setDate(new Date(2030, 5, 1, 14, 0), true));

    await openSection(page, /^Location/);
    await page.locator('#venue').fill('E2E Venue');

    await openSection(page, /^Venue Permissions/);
    await page.locator('#allow_blasters').selectOption('0');

    await page.getByRole('button', { name: 'Create', exact: true }).click();

    await expect(page.locator('#name')).toHaveClass(/is-invalid/);
    await expect(page.locator('input[name="picker-organization_id"]')).toHaveValue('501st Legion');
    await expect(page.locator('#event_start')).toHaveValue('2030-06-01 10:00AM');
    await expect(page.locator('#event_end')).toHaveValue('2030-06-01 2:00PM');
    await expect(page.locator('#venue')).toHaveValue('E2E Venue');
    await expect(page.locator('#allow_blasters')).toHaveValue('0');
    await expect(page.locator('#tentative_signups_allowed')).toHaveValue('1');
});
