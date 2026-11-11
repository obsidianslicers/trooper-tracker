// A trooper's own account: profile, notification settings, costumes, club join requests,
// notices, and asking for (then cancelling) account deletion.
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';
import { signInAndLeaveLogin } from './support/auth.js';

test.use({ storageState: personas.account.storageState });

async function openTab(page, name) {
    await page.goto('/account');
    await page.locator('#app button.nav-link').filter({ hasText: name }).first().click();
}

async function saved(page, action) {
    const response = page.waitForResponse((r) => r.url().includes('/account/') && r.request().method() !== 'GET');
    await action();
    await response;
    await page.waitForLoadState('networkidle');
}

test.describe('profile', () => {
    test('a trooper updates their display name, phone and theme', async ({ page }) => {
        await openTab(page, 'Profile');

        await page.getByLabel('Display Name').fill('E2E Account Renamed');
        await page.getByLabel('Phone (optional)').fill('555-0100');
        await page.getByLabel('Theme').selectOption({ label: 'Rebel' });
        await saved(page, () => page.getByRole('button', { name: 'Save' }).click());

        await openTab(page, 'Profile');
        await expect(page.getByLabel('Display Name')).toHaveValue('E2E Account Renamed');
        await expect(page.getByLabel('Phone (optional)')).toHaveValue(/555-?0100/);
        await expect(page.getByLabel('Theme')).toHaveValue('rebel');
    });

    test('a display name is required', async ({ page }) => {
        await openTab(page, 'Profile');

        await page.getByLabel('Display Name').fill('');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.locator('form').getByText('The display name field is required.')).toBeVisible();
    });
});

test.describe('notification settings', () => {
    test('a trooper changes how often they hear from the tracker, and which clubs', async ({ page }) => {
        await openTab(page, 'Notifications');
        await page.getByRole('button', { name: /Organization Event Notifications/ }).click();

        await saved(page, () => page.getByLabel('Notification Frequency').selectOption({ label: 'Daily' }));

        const club = page.getByLabel('Saber Guild');
        const was_on = await club.isChecked();
        await saved(page, () => club.setChecked(!was_on));

        await openTab(page, 'Notifications');
        await page.getByRole('button', { name: /Organization Event Notifications/ }).click();
        await expect(page.getByLabel('Notification Frequency')).toHaveValue('daily');
        await expect(page.getByLabel('Saber Guild')).toBeChecked({ checked: !was_on });
    });
});

test.describe('costumes', () => {
    test('a trooper adds a costume to their armory and removes one', async ({ page }) => {
        await openTab(page, 'Costumes');
        await expect(page.getByRole('cell', { name: /E2E Account Armor/ })).toBeVisible();

        await page.getByLabel('Find Your Costume').fill('E2E Spare');
        await page.getByRole('button', { name: seeded.spareCostume }).click();
        await page.getByRole('checkbox', { name: '501st Legion' }).check();
        await saved(page, () => page.getByRole('button', { name: 'Add to Armory' }).click());

        await expect(page.getByRole('cell', { name: new RegExp(seeded.spareCostume) })).toBeVisible();

        const row = page.getByRole('row').filter({ hasText: 'E2E Account Armor' });
        await saved(page, () => row.getByRole('button').click());

        await openTab(page, 'Costumes');
        await expect(page.getByRole('cell', { name: /E2E Account Armor/ })).toHaveCount(0);
        await expect(page.getByRole('cell', { name: new RegExp(seeded.spareCostume) })).toBeVisible();
    });
});

test.describe('club memberships', () => {
    test('a trooper asks to join another club', async ({ page }) => {
        await openTab(page, 'Memberships');

        await page.getByLabel('Organization to Join').selectOption({ label: 'Rebel Legion' });
        await page.getByLabel('Forum Username').fill('e2e-rebel');
        await saved(page, () => page.getByRole('button', { name: 'Request Access' }).click());

        await openTab(page, 'Memberships');
        await expect(page.getByText('Rebel Legion').first()).toBeVisible();
        await expect(page.getByText(/pending/i).first()).toBeVisible();
    });
});

test.describe('notices', () => {
    test('a trooper reads the notices for their club', async ({ page }) => {
        await page.goto('/account/notices');

        await expect(page.getByText(seeded.notice).first()).toBeVisible();
    });

    test('a notice shows on the event list until the trooper dismisses it', async ({ page }) => {
        await page.goto('/events');
        const notice = page.locator('.alert').filter({ hasText: seeded.notice });
        await expect(notice).toBeVisible();

        await saved(page, () => notice.locator('.btn-close').click());
        await expect(notice).toHaveCount(0);

        await page.reload();
        await expect(page.getByText('E2E Signup Troop').first()).toBeVisible();
        await expect(page.locator('.alert').filter({ hasText: seeded.notice })).toHaveCount(0);
    });
});

test.describe('deleting an account', () => {
    test('a trooper asks for deletion, must confirm it, and can sign back in to cancel', async ({ page }) => {
        await openTab(page, 'Delete Account');

        await page.locator('#app .btn').filter({ hasText: 'Delete Account' }).click();
        const confirm = page.getByLabel('Type DELETE to confirm');
        await confirm.fill('delete please');
        await expect(page.getByRole('button', { name: 'Delete', exact: true })).toBeDisabled();

        await confirm.fill('DELETE');
        await page.getByRole('button', { name: 'Delete', exact: true }).click();

        // They are signed out, and signing back in within 30 days offers to cancel.
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByText('Your account has been scheduled for permanent deletion.')).toBeVisible();

        await signInAndLeaveLogin(page, personas.account.email);
        await page.goto('/account');
        await expect(page.getByRole('button', { name: 'Cancel Deletion' })).toBeVisible();
        await expect(page.locator('#app button.nav-link').filter({ hasText: 'Delete Account' })).toHaveCount(0);

        await saved(page, () => page.getByRole('button', { name: 'Cancel Deletion' }).click());

        await page.goto('/account');
        await expect(page.getByRole('button', { name: 'Cancel Deletion' })).toHaveCount(0);
        await expect(page.locator('#app button.nav-link').filter({ hasText: 'Delete Account' })).toBeVisible();
    });
});
