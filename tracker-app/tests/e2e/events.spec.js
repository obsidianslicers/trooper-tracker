// The trooper's side of events: finding a troop, signing up and changing that sign-up,
// bringing friends and guests, the mission brief, and saying whether they made it.
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';

test.use({ storageState: personas.trooper.storageState });

async function openEvent(page, name, list = '/events') {
    await page.goto(list);
    await page.getByRole('link', { name }).first().click();
    await expect(page.getByRole('heading', { name })).toBeVisible();
}

function shift(page) {
    return page.locator('[id^="shift-container-"]').first();
}

// Selects post with HTMX and swap the shift in place; wait for the new one.
async function choose(page, name, option) {
    const response = page.waitForResponse((r) => r.request().method() === 'POST');
    await shift(page).locator(`select[name="${name}"]`).selectOption(option);
    await response;
    await page.waitForLoadState('networkidle');
}

test.describe('finding a troop', () => {
    test('the list, calendar and map show upcoming troops', async ({ page }) => {
        await page.goto('/events');
        await expect(page.getByRole('link', { name: seeded.signupEvent }).first()).toBeVisible();

        await page.goto('/events/calendar');
        await expect(page.getByText(seeded.signupEvent).first()).toBeAttached();

        // The map itself needs Google, which the test browser may not reach.
        const map = await page.goto('/events/map');
        expect(map?.status()).toBe(200);
    });

    test('completed troops are listed apart from open ones', async ({ page }) => {
        await page.goto('/events/closed');
        await expect(page.getByText(seeded.pastParade).first()).toBeVisible();

        await page.goto('/events');
        await expect(page.getByRole('link', { name: seeded.pastParade })).toHaveCount(0);
    });
});

test.describe('signing up', () => {
    test('a trooper signs up, picks a costume, brings a guest and a friend, then cancels', async ({ page }) => {
        await openEvent(page, seeded.signupEvent);

        await shift(page).getByRole('button', { name: 'Sign Up' }).click();
        await expect(shift(page).getByText('Select a costume, or mark attending without one')).toBeVisible();

        await choose(page, 'costume_id', { label: seeded.trooperCostume });
        await expect(shift(page).getByText('1 / 10 going')).toBeVisible();
        await expect(shift(page).locator('select[name="status"]')).toHaveValue('going');

        await choose(page, 'backup_costume_id', { label: seeded.trooperCostume });

        // A guest, by name.
        await shift(page).getByRole('button', { name: 'Add a Guest' }).click();
        const guests = page.getByRole('dialog');
        await guests.locator('textarea[name="guest_names"]').fill('E2E Guest Wookiee');
        await guests.getByRole('button', { name: 'Save Guests' }).click();
        await expect(shift(page).getByText('E2E Guest Wookiee')).toBeVisible();

        // A friend from the tracker.
        await shift(page).getByRole('button', { name: 'Add a Friend' }).click();
        const picker = page.getByRole('dialog');
        await picker.getByRole('textbox').fill('E2E Trooper Friend');
        await picker.getByRole('listitem').filter({ hasText: seeded.friend.name }).click();
        await expect(shift(page).getByRole('link', { name: seeded.friend.name })).toBeVisible();

        // Backing out.
        const own = shift(page).locator('.row').filter({ has: page.getByRole('link', { name: personas.trooper.name, exact: true }) });
        const response = page.waitForResponse((r) => r.request().method() === 'POST');
        await own.locator('select[name="status"]').selectOption('cancelled');
        await response;
        await expect(shift(page).locator('.trooper-status-cancelled')).toContainText(personas.trooper.name);
    });

    test('a troop with a required brief opens sign-up once it is acknowledged', async ({ page }) => {
        await openEvent(page, seeded.briefedEvent);

        await expect(page.getByText('Review and acknowledge the mission brief above to enable sign-ups.')).toBeVisible();
        await expect(shift(page).getByRole('button', { name: 'Sign Up' })).toHaveCount(0);

        await page.getByRole('button', { name: 'Decrypt Mission Brief' }).click();
        await expect(page.getByText(seeded.missionBrief)).toBeVisible();

        await page.getByRole('button', { name: 'I have read and understand the mission brief' }).click();

        await expect(page.getByText('You have acknowledged this mission brief.')).toBeVisible();
        await expect(shift(page).getByRole('button', { name: 'Sign Up' })).toBeVisible();
    });
});

test.describe('around the event page', () => {
    test('a trooper watches and unwatches an event', async ({ page }) => {
        await openEvent(page, seeded.signupEvent);

        const toggle = page.locator('#event-watch-toggle button');
        await expect(toggle).toHaveAttribute('title', 'Watch Event');

        await toggle.click();
        await expect(page.locator('#event-watch-toggle button')).toHaveAttribute('title', 'Watching');

        await page.locator('#event-watch-toggle button').click();
        await expect(page.locator('#event-watch-toggle button')).toHaveAttribute('title', 'Watch Event');
    });

    test('the event downloads as a calendar file', async ({ page }) => {
        await openEvent(page, seeded.signupEvent);

        const url = page.url().replace(/\/$/, '') + '/calendar.ics';
        const response = await page.request.get(url);

        expect(response.status()).toBe(200);
        expect(response.headers()['content-type']).toContain('text/calendar');
        expect(await response.text()).toContain(seeded.signupEvent);
    });

    test('the roster download is for Command Staff only', async ({ page }) => {
        await openEvent(page, seeded.signupEvent);

        const response = await page.request.get(page.url().replace(/\/$/, '') + '/roster-csv');

        expect(response.status()).toBe(403);
    });

    test('a trooper uploads a photo to the mission review', async ({ page }) => {
        await openEvent(page, seeded.signupEvent);

        await page.locator('input.image-input').setInputFiles({
            name: 'e2e-photo.png',
            mimeType: 'image/png',
            buffer: PNG,
        });

        await expect(page.locator('#event-uploads img')).not.toHaveCount(0);
    });
});

test.describe('after the troop', () => {
    test('a trooper says they made it', async ({ page }) => {
        await openEvent(page, seeded.pastParade, '/events/closed');

        await shift(page).getByRole('link', { name: 'Made It' }).click();

        await openEvent(page, seeded.pastParade, '/events/closed');
        await expect(shift(page).getByText('Attended')).toBeVisible();
    });
});

// A 1x1 transparent PNG.
const PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
    'base64',
);
