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

// The "Your sign-up" card at the top (the phone bar below it is hidden at this width)
function summary(page) {
    return page.locator('.my-signup-summary').filter({ visible: true });
}

async function collapseShift(page) {
    await shift(page).locator('.card-header').click();
    await expect(shift(page).locator('[id^="shift-card-"]')).toBeHidden();
}

// Each picker in a roster row saves as soon as it changes, and the shift redraws.
async function choose(page, row, label, option) {
    const response = page.waitForResponse((r) => r.url().includes('/events/update/') && r.request().method() === 'POST');
    await row.getByLabel(label, { exact: true }).selectOption(option);
    await response;
    await page.waitForLoadState('networkidle');
}

// The page's own confirmation box, in place of the browser's confirm()
async function confirmWith(page, button) {
    await page.locator('.modal.show').getByRole('button', { name: button, exact: true }).click();
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

    test('the map has the same views menu and search as the other event views', async ({ page }) => {
        // The map itself needs Google, which the test browser may not reach.
        await page.goto('/events/map');
        const menu = page.getByRole('button', { name: 'Event views' }).filter({ visible: true });
        await expect(menu).toHaveText('Map');

        await page.getByRole('searchbox', { name: 'Search events' }).fill('no event is called this');
        await expect(page.getByText('0 events').filter({ visible: true })).toBeVisible();
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

        // Sign Up shows the choices; Confirm sends them, the costume with it.
        await shift(page).getByRole('button', { name: 'Sign Up' }).click();
        const form = shift(page).locator('.shift-signup .shift-signup-primary--form');
        await form.getByLabel('Costume', { exact: true }).selectOption({ label: seeded.trooperCostume });
        const confirm = form.getByRole('button', { name: 'Confirm' });
        await expect(confirm).toBeEnabled();
        const signedUp = page.waitForResponse((r) => r.url().includes('/events/signup/') && r.request().method() === 'POST');
        await confirm.click();
        await signedUp;
        await expect(shift(page).getByText('1 of 10 going')).toBeVisible();
        const myRow = shift(page).locator('.roster-row--mine');
        await expect(myRow).toContainText('Going');
        await expect(summary(page)).toContainText(seeded.trooperCostume);

        // "Change" in the summary opens the shift again when it was collapsed, and your row's Edit area.
        await collapseShift(page);
        await expect(myRow).toBeHidden();
        await summary(page).getByRole('link', { name: 'Change' }).click();
        await expect(myRow.getByLabel('Costume', { exact: true })).toBeVisible();

        // The backup costume waits behind its button; picking one saves and says so, and the Edit
        // area stays open after the shift is redrawn.
        await myRow.getByRole('button', { name: 'Add a backup costume' }).click();
        await choose(page, myRow, 'Backup costume (optional)', { label: seeded.trooperCostume });
        await expect(page.locator('.toast:visible').getByText('Saved.').first()).toBeVisible();
        await expect(myRow.getByLabel('Costume', { exact: true })).toBeVisible();

        // A guest, by name, in the same kind of box as Sign Up; their row gets an Edit button.
        await shift(page).getByRole('button', { name: 'Add a guest' }).click();
        await shift(page).getByPlaceholder("Guest's name").fill('E2E Guest Wookiee');
        await shift(page).locator('.shift-links .shift-signup-primary--form').getByRole('button', { name: 'Confirm' }).click();
        const guestRow = () => shift(page).locator('.roster-row[id^="roster-guest-"]');
        await expect(guestRow()).toContainText('E2E Guest Wookiee');
        await expect(guestRow().getByRole('button', { name: 'Edit' })).toBeVisible();

        // A friend from the tracker, the same way: pick them, then their costume (their only one is
        // preselected) and Confirm.
        await shift(page).getByRole('button', { name: 'Add a friend' }).click();
        const picker = page.locator('.modal.show');
        await picker.getByRole('textbox').fill('E2E Trooper Friend');
        await picker.getByRole('button', { name: new RegExp(seeded.friend.name) }).click();
        const friendForm = shift(page).locator('.friend-signup-step .shift-signup-primary--form');
        await expect(friendForm).toContainText(`Sign up ${seeded.friend.name}`);
        await expect(friendForm.getByLabel('Costume', { exact: true }).locator('option:checked')).toHaveText(seeded.friendCostume);
        const friendSignedUp = page.waitForResponse((r) => r.url().includes('/events/signup/') && r.request().method() === 'POST');
        await friendForm.getByRole('button', { name: 'Confirm' }).click();
        await friendSignedUp;
        // Going straight away, not waiting on a costume.
        await expect(shift(page).getByText('2 of 10 going')).toBeVisible();
        // Their row shows their costume; its pickers wait behind Edit, like yours.
        const friendRow = shift(page).locator('.roster-row').filter({ hasText: seeded.friend.name });
        await expect(friendRow.getByLabel('Costume', { exact: true })).toBeHidden();
        await friendRow.getByRole('button', { name: 'Edit' }).click();
        await expect(friendRow.getByLabel('Costume', { exact: true }).locator('option:checked')).toHaveText(seeded.friendCostume);

        // Cancelling the guest, from their Edit area, asks first.
        await guestRow().getByRole('button', { name: 'Edit' }).click();
        const guestCancelled = page.waitForResponse((r) => r.url().includes('/guest') && r.url().includes('/events/update/') && r.request().method() === 'POST');
        await guestRow().getByRole('button', { name: 'Cancel their sign-up' }).click();
        await confirmWith(page, 'Cancel their sign-up');
        await guestCancelled;
        await expect(guestRow().getByRole('button', { name: 'Sign up again' })).toBeVisible();

        // Backing out: a button in your Edit area that asks first, not an option in a dropdown.
        const response = page.waitForResponse((r) => r.url().includes('/events/update/') && r.request().method() === 'POST');
        await shift(page).locator('.roster-row--mine').getByRole('button', { name: 'Cancel my sign-up' }).click();
        await confirmWith(page, 'Cancel my sign-up');
        await response;
        await expect(shift(page).locator('.trooper-status-cancelled')).toContainText(personas.trooper.name);
        await expect(shift(page).locator('.trooper-status-cancelled').getByRole('button', { name: 'Sign up again' })).toBeVisible();
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

        await page.getByRole('button', { name: 'Watch Event' }).click();
        await expect(page.locator('.toast:visible').getByText("You're watching this event.").first()).toBeVisible();
        await expect(page.getByRole('button', { name: 'Watching' })).toHaveAttribute('aria-pressed', 'true');

        await page.getByRole('button', { name: 'Watching' }).click();
        await expect(page.getByRole('button', { name: 'Watch Event' })).toHaveAttribute('aria-pressed', 'false');
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
        await expect(shift(page).locator('.roster-row--mine')).toContainText('Attended');
    });
});

// A 1x1 transparent PNG.
const PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
    'base64',
);
