import { test, expect } from '@playwright/test';
import { personas } from './support/personas.js';
import { field, pickHost } from './support/event-form.js';

test.use({ storageState: personas.admin.storageState });

async function openSection(page, label) {
    await page.getByRole('button', { name: label }).click();
}

function chosen(page, label) {
    return field(page, label).locator('option:checked');
}

test('a create that fails validation keeps what was entered', async ({ page }) => {
    await page.goto('/admin/events/create');

    await pickHost(page, '501st Legion');
    await page.getByText('Input Manually').click();

    // The Yes/No fields open on the server's defaults.
    await openSection(page, /^Character Requests/);
    await expect(chosen(page, 'Tentative Sign-Ups Allowed (per shift)')).toHaveText('Yes');
    await openSection(page, /^Miscellaneous/);
    await expect(chosen(page, 'Require Mission Brief Acknowledgement Before Signup')).toHaveText('No');

    // Leave the name blank so the submit fails, and fill in the rest.
    await field(page, 'Starts').fill('2030-06-01 10:00AM');
    await field(page, 'Ends').fill('2030-06-01 2:00PM');

    await openSection(page, /^Location/);
    await field(page, 'Venue').fill('E2E Venue');

    await openSection(page, /^Venue Permissions/);
    await field(page, 'Allow Blasters').selectOption({ label: 'No' });

    await page.getByRole('button', { name: 'Create', exact: true }).click();

    await expect(field(page, 'Name')).toHaveClass(/is-invalid/);
    await expect(field(page, 'Hosting Organization')).toHaveValue('501st Legion');
    await expect(field(page, 'Starts')).toHaveValue('2030-06-01 10:00AM');
    await expect(field(page, 'Ends')).toHaveValue('2030-06-01 2:00PM');
    await expect(field(page, 'Venue')).toHaveValue('E2E Venue');
    await expect(chosen(page, 'Allow Blasters')).toHaveText('No');
    await expect(chosen(page, 'Tentative Sign-Ups Allowed (per shift)')).toHaveText('Yes');
});

test('a pasted request fills the form in for the host club', async ({ page }) => {
    await page.goto('/admin/events/create');
    await pickHost(page, '501st Legion');

    await expect(page.getByLabel('501st Legion Message')).toBeVisible();
    await page.getByLabel('501st Legion Message').fill(`Event Name: E2E Pasted Troop
Venue: Main Library
Event Start: 12/13/2030 - 0930
Event End: 12/13/2030 - 1300
Can troopers carry blasters: No`);
    await page.getByLabel('501st Legion Message').blur();

    await expect(field(page, 'Name')).toHaveValue('E2E Pasted Troop');
    await expect(field(page, 'Starts')).toHaveValue('2030-12-13 9:30AM');
    await openSection(page, /^Venue Permissions/);
    await expect(chosen(page, 'Allow Blasters')).toHaveText('No');
});

test('on a phone, the events list shows its Create button without scrolling the table', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/admin/events');

    const create = page.locator('a.btn[href*="/admin/events/create"]');
    await expect(create).toBeInViewport({ ratio: 1 });

    await create.click();
    await expect(page).toHaveURL(/\/admin\/events\/create/);
});
