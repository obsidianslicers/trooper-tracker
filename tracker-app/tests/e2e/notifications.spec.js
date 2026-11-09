import { test, expect } from '@playwright/test';
import { personas } from './support/personas.js';

test.use({ storageState: personas.member.storageState });

const TITLE = 'E2E Open Troop is open for sign-up';

function bell(page) {
    // the account page has a "Notifications" tab of its own
    return page.locator('.navbar').getByRole('button', { name: 'Notifications', exact: true });
}

function menu(page) {
    return page.locator('.notifications-menu');
}

test('the bell opens the notifications in place, and they work on both kinds of page', async ({ page }) => {
    // A Blade page: the unread one is counted, opens from the bell and leads to its event.
    await page.goto('/faq');
    await expect(bell(page).locator('.badge')).toHaveText('1');

    await bell(page).click();
    await expect(menu(page)).toBeVisible();
    await menu(page).getByRole('button', { name: new RegExp(TITLE) }).click();

    await expect(page).toHaveURL(/\/events\/details\/\d+$/);
    await expect(bell(page).locator('.badge')).toHaveCount(0);

    // A Svelte page: the same list, and Clear all comes back to this page.
    await page.goto('/account');
    await bell(page).click();
    await expect(menu(page).getByText(TITLE)).toBeVisible();

    await menu(page).getByRole('button', { name: 'Clear all' }).click();

    await expect(page).toHaveURL(/\/account$/);
    await bell(page).click();
    await expect(menu(page).getByText('No notifications yet.')).toBeVisible();

    // The full list is still a page of its own.
    await menu(page).getByRole('link', { name: 'See all notifications' }).click();
    await expect(page).toHaveURL(/\/account\/push-notifications$/);
    await expect(page.locator('main, .main-content').getByText('No notifications yet.')).toBeVisible();
});
