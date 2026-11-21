// The trooper's side of events: finding a troop, signing up and changing that sign-up,
// bringing friends and guests, the mission brief, and saying whether they made it.
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';
import { htmxSettled } from './support/htmx.js';

test.use({ storageState: personas.trooper.storageState });

async function openEvent(page, name, list = '/events') {
    await page.goto(list);
    await page.getByRole('link', { name }).first().click();
    await expect(page.getByRole('heading', { name })).toBeVisible();
}

function shift(page) {
    return page.locator('[id^="shift-container-"]').first();
}

// The "Your sign-up" card at the top (the phone bar below it is hidden at this width)
function summary(page) {
    return page.locator('#my-signup-summary .my-signup-summary');
}

async function collapseShift(page) {
    // The header toggles once the deferred Bootstrap bundle has run
    await page.waitForLoadState('load');
    await shift(page).locator('.card-header').click();
    // Hidden only once the animation ends; Bootstrap ignores show() until then.
    await expect(shift(page).locator('[id^="shift-card-"]')).toBeHidden();
}

// Each select posts with HTMX and swaps the shift in place; wait for the new one.
async function choose(page, name, option) {
    await htmxSettled(page);
    const response = page.waitForResponse((r) => r.url().includes('/events/update/') && r.request().method() === 'POST');
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

    test('the views menu beside the search box switches between event views', async ({ page }) => {
        await page.goto('/events');
        const menu = page.getByRole('button', { name: 'Event views' }).filter({ visible: true });
        await expect(menu).toHaveText('List');

        await menu.click();
        await page.getByRole('link', { name: 'Calendar' }).filter({ visible: true }).click();
        await expect(page).toHaveURL(/\/events\/calendar$/);
        await expect(menu).toHaveText('Calendar');

        await menu.click();
        await page.getByRole('link', { name: 'Completed' }).filter({ visible: true }).click();
        await expect(page).toHaveURL(/\/events\/closed$/);
        await expect(menu).toHaveText('Completed');
    });

    test('completed troops are listed apart from open ones', async ({ page }) => {
        await page.goto('/events/closed');
        await expect(page.getByText(seeded.pastParade).first()).toBeVisible();

        await page.goto('/events');
        await expect(page.getByRole('link', { name: seeded.pastParade })).toHaveCount(0);
    });
});

test.describe('signing up', () => {
    test("the summary's Sign up opens a collapsed shift", async ({ page }) => {
        await openEvent(page, seeded.signupEvent);
        const signUp = shift(page).getByRole('button', { name: 'Sign Up' });
        await collapseShift(page);
        await expect(signUp).toBeHidden();

        await summary(page).getByRole('link', { name: 'Sign up' }).click();
        await expect(signUp).toBeVisible();
        await expect(shift(page).locator('.collapse-icon')).toHaveClass(/fa-minus/);
    });

    test('a trooper signs up in a costume, brings a guest and a friend, then cancels', async ({ page }) => {
        await openEvent(page, seeded.signupEvent);
        await expect(page.getByText("You're not signed up for this event yet.")).toBeVisible();

        // One step: the costume goes with the Sign Up button.
        const signUp = shift(page).getByRole('button', { name: 'Sign Up' });
        await shift(page).locator('select[name="costume_id"]').selectOption({ label: seeded.trooperCostume });
        await expect(signUp).toBeEnabled();
        await htmxSettled(page);
        const signedUp = page.waitForResponse((r) => r.url().includes('/events/signup/') && r.request().method() === 'POST');
        await signUp.click();
        await signedUp;
        await expect(shift(page).getByText('1 / 10 going')).toBeVisible();
        await expect(shift(page).locator('[id^="my-shift-signup-"]')).toContainText('Going');
        await expect(page.locator('#my-signup-summary')).toContainText(seeded.trooperCostume);

        // "Change" in the summary opens the shift again when it was collapsed.
        await collapseShift(page);
        await expect(shift(page).locator('[id^="my-shift-signup-"]')).toBeHidden();
        await summary(page).getByRole('link', { name: 'Change' }).click();
        await expect(shift(page).locator('[id^="my-shift-signup-"]')).toBeVisible();

        await choose(page, 'backup_costume_id', { label: seeded.trooperCostume });
        await expect(page.locator('.toast:visible').getByText('Saved.').first()).toBeVisible();

        // A guest, by name, right in "Your sign-up", where they're listed and changed from then on.
        await shift(page).getByRole('button', { name: 'Add a Guest' }).click();
        await shift(page).getByPlaceholder("Guest's name").fill('E2E Guest Wookiee');
        await htmxSettled(page);
        await shift(page).getByRole('button', { name: 'Add Guest' }).click();
        const myGuest = shift(page).locator('[id^="my-shift-guest-"]');
        await expect(myGuest).toContainText('E2E Guest Wookiee');
        await expect(myGuest.getByRole('button', { name: 'Cancel' })).toBeVisible();

        // A friend from the tracker, in one step like your own: pick them, then their costume
        // (their only one is preselected) goes with Sign Up.
        await shift(page).getByRole('button', { name: 'Add a Friend' }).click();
        const picker = page.getByRole('dialog');
        // The picker loads into the dialog with HTMX; typing before it settles sends no search.
        await expect(picker.getByRole('textbox')).toBeVisible();
        await htmxSettled(page);
        await picker.getByRole('textbox').fill('E2E Trooper Friend');
        await picker.getByRole('listitem').filter({ hasText: seeded.friend.name }).click();
        const friendForm = shift(page).locator('[id^="add-trooper-step2-"] .friend-signup-confirmation');
        await expect(friendForm).toContainText(`Sign up ${seeded.friend.name}`);
        await expect(friendForm.locator('select[name="costume_id"] option:checked')).toHaveText(seeded.friendCostume);
        await htmxSettled(page);
        const friendSignedUp = page.waitForResponse((r) => r.url().includes('/events/signup/') && r.request().method() === 'POST');
        await friendForm.getByRole('button', { name: 'Sign Up' }).click();
        await friendSignedUp;
        // Going straight away, not waiting on a costume.
        await expect(shift(page).getByText('2 / 10 going')).toBeVisible();
        // Listed under "You're bringing" with the same pickers as yours; every roster row is read-only.
        const myFriend = shift(page).locator('[id^="my-shift-friend-"]');
        await expect(myFriend).toContainText(seeded.friend.name);
        await expect(myFriend.locator('select[name="costume_id"] option:checked')).toHaveText(seeded.friendCostume);
        await expect(shift(page).locator('[class*="trooper-status-"] select')).toHaveCount(0);
        await expect(shift(page).locator('[class*="guest-status-"] select')).toHaveCount(0);

        // Cancelling the guest, there too.
        await htmxSettled(page);
        page.once('dialog', (dialog) => dialog.accept());
        const guestCancelled = page.waitForResponse((r) => r.url().includes('/guest') && r.url().includes('/events/update/') && r.request().method() === 'POST');
        await myGuest.getByRole('button', { name: 'Cancel' }).click();
        await guestCancelled;
        await expect(shift(page).locator('[id^="my-shift-guest-"]').getByRole('button', { name: 'Sign up again' })).toBeVisible();

        // Backing out: a button that asks first, not an option in a dropdown.
        await htmxSettled(page);
        page.once('dialog', (dialog) => dialog.accept());
        const response = page.waitForResponse((r) => r.url().includes('/events/update/') && r.request().method() === 'POST');
        await shift(page).getByRole('button', { name: 'Cancel my sign-up' }).click();
        await response;
        await expect(shift(page).locator('.trooper-status-cancelled')).toContainText(personas.trooper.name);
        await expect(shift(page).getByRole('button', { name: 'Sign up again' }).first()).toBeVisible();
    });

    test('a troop with a required brief opens sign-up once it is acknowledged', async ({ page }) => {
        await openEvent(page, seeded.briefedEvent);

        await expect(page.getByText('Review and acknowledge the mission brief above to enable sign-ups.')).toBeVisible();
        await expect(shift(page).getByRole('button', { name: 'Sign Up' })).toHaveCount(0);

        // A short brief shows in full, with nothing to decrypt.
        await expect(page.getByText(seeded.missionBrief)).toBeVisible();
        await expect(page.getByRole('button', { name: 'Decrypt Mission Brief' })).toBeHidden();
        await expect(page.getByText("(Click 'Decrypt mission brief')")).toBeHidden();

        await page.getByRole('button', { name: 'I have read and understand the mission brief' }).click();

        await expect(page.getByText('You have acknowledged this mission brief.')).toBeVisible();
        await expect(shift(page).getByRole('button', { name: 'Sign Up' })).toBeVisible();
    });

    test('a long brief starts collapsed and says to decrypt it', async ({ page }) => {
        await openEvent(page, seeded.longBriefEvent);

        await expect(page.getByText("(Click 'Decrypt mission brief')")).toBeVisible();
        const acknowledge = page.getByRole('button', { name: 'I have read and understand the mission brief' });
        await expect(acknowledge).toBeHidden();

        await page.getByRole('button', { name: 'Decrypt Mission Brief' }).click();
        await expect(acknowledge).toBeVisible();
        await expect(page.getByText('step 10: check in')).toBeVisible();
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

        await page.locator('label[for="upload-type-troop"]').click();
        await page.locator('input.image-input').setInputFiles({
            name: 'e2e-photo.png',
            mimeType: 'image/png',
            buffer: PNG,
        });

        await expect(page.locator('#event-uploads img')).not.toHaveCount(0);
    });

    test('a photo added before the troop goes to the instructional uploads', async ({ page }) => {
        await openEvent(page, seeded.signupEvent);

        await expect(page.locator('#upload-type-instructional')).toBeChecked();
        await page.locator('input.image-input').setInputFiles({
            name: 'e2e-map.png',
            mimeType: 'image/png',
            buffer: PNG,
        });

        await expect(page.locator('#admin-event-uploads img')).not.toHaveCount(0);
    });
});

test.describe('after the troop', () => {
    test('a trooper says they made it', async ({ page }) => {
        await openEvent(page, seeded.pastParade, '/events/closed');

        await shift(page).getByRole('link', { name: 'Made It' }).click();

        await openEvent(page, seeded.pastParade, '/events/closed');
        await expect(shift(page).locator('[id^="my-shift-signup-"]')).toContainText('Attended');
    });
});

// A 1x1 transparent PNG.
const PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
    'base64',
);
