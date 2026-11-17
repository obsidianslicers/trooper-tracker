// Command Staff running an event: creating, copying and editing it, its shifts and stations,
// charity numbers, the roster, uploads and the mission review, and cancelling it. The event page
// is one Svelte page with a tab for each; every tab but Overview saves as changes are made.
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';
import { signInAndLeaveLogin } from './support/auth.js';
import { tinker } from './support/artisan.js';

test.use({ storageState: personas.moderator.storageState });

// The Command Staff event list, searched to one event; each row's menu links to its pages.
function adminRow(page, name) {
    return page.locator('tbody tr').filter({ hasText: name });
}

async function openAdmin(page, name, tab = 'overview') {
    await page.goto(`/admin/events?search_term=${encodeURIComponent(name)}`);
    const href = await adminRow(page, name).first().locator('a[href$="/update"]').getAttribute('href');

    await page.goto(`${href}?tab=${tab}`);
}

// With more than one shift the roster starts folded; open every shift.
async function expandShifts(page) {
    const folded = page.locator('.roster-shift-toggle[aria-expanded="false"]');

    while (await folded.count() > 0) {
        await folded.first().click();
    }
}

// Runs an action that saves (the Update button, or a change on a tab that saves as you go) and
// waits for the save to finish.
async function saved(page, action) {
    const response = page.waitForResponse((r) => r.url().includes('/admin/events/') && r.request().method() === 'POST');
    await action();
    await response;
    await page.waitForLoadState('networkidle');
}

// Text fields save when you leave them, as a person tabbing through the form would.
async function fillAndLeave(locator, value) {
    await locator.fill(value);
    await locator.press('Tab');
}

function field(page, label) {
    return page.getByLabel(label, { exact: true });
}

async function confirmDialog(page, button) {
    const dialog = page.getByRole('dialog');
    await saved(page, () => dialog.getByRole('button', { name: button, exact: true }).click());
}

async function openSection(page, label) {
    await page.getByRole('button', { name: label }).click();
}

async function pickHost(page, name) {
    await page.locator('#picker-container-organization_id').click();
    const modal = page.locator('#modal-picker');
    await modal.locator(`li[data-name="${name}"]`).click();
    await expect(modal).toBeHidden();
}

function updateButton(page) {
    return page.getByRole('button', { name: 'Update', exact: true });
}

test.describe('event details', () => {
    test('a moderator creates an event, which troopers can then find', async ({ page }) => {
        await page.goto('/admin/events/create');

        await pickHost(page, '501st Legion');
        await page.getByText('Input Manually').click();
        await page.locator('#name').fill('E2E Created Troop');

        await openSection(page, 'Schedule');
        await page.locator('#event_start').fill('2030-06-01 10:00AM');
        await page.locator('#event_end').fill('2030-06-01 2:00PM');

        await openSection(page, /^Location/);
        await page.locator('#venue').fill('E2E Created Venue');

        await page.getByRole('button', { name: 'Create', exact: true }).click();

        await expect(page).toHaveURL(/\/admin\/events\/\d+\/update$/);
        await expect(field(page, 'Name')).toHaveValue('E2E Created Troop');

        await page.goto(await page.getByRole('link', { name: 'View Event' }).getAttribute('href'));
        await expect(page.getByRole('heading', { name: 'E2E Created Troop' })).toBeVisible();
        await expect(page.getByText('E2E Created Venue')).toBeVisible();
    });

    test('a moderator renames an event', async ({ page }) => {
        await openAdmin(page, seeded.commandEvent);

        await field(page, 'Name').fill('E2E Command Troop Renamed');
        await saved(page, () => updateButton(page).click());

        await page.reload();
        await expect(field(page, 'Name')).toHaveValue('E2E Command Troop Renamed');

        // Put it back for the rest of the spec.
        await field(page, 'Name').fill(seeded.commandEvent);
        await saved(page, () => updateButton(page).click());
        await expect(field(page, 'Name')).toHaveValue(seeded.commandEvent);
    });

    test('a moderator copies an event into a new one', async ({ page }) => {
        await page.goto('/events');
        await page.getByRole('link', { name: seeded.copySourceEvent }).first().click();

        await page.getByRole('link', { name: 'Copy' }).first().click();
        await expect(page).toHaveURL(/\/admin\/events\/\d+\/copy$/);

        await page.locator('#name').fill('E2E Copied Troop');
        await page.getByRole('button', { name: /^(Copy|Create|Save)$/ }).click();

        await expect(page).toHaveURL(/\/admin\/events\/\d+\/update$/);
        await expect(field(page, 'Name')).toHaveValue('E2E Copied Troop');

        await page.goto('/admin/events?search_term=E2E+Cop');
        await expect(adminRow(page, seeded.copySourceEvent)).toHaveCount(1);
        await expect(adminRow(page, 'E2E Copied Troop')).toHaveCount(1);
    });

    test('a moderator cancels an event, and troopers see it under cancelled', async ({ page }) => {
        await openAdmin(page, seeded.cancelEvent);

        await field(page, 'Status').selectOption('cancelled');
        await saved(page, () => updateButton(page).click());

        await page.reload();
        await expect(field(page, 'Status')).toHaveValue('cancelled');

        await page.goto('/events/cancelled');
        await expect(page.getByText(seeded.cancelEvent).first()).toBeVisible();

        await page.goto('/events');
        await expect(page.getByRole('link', { name: seeded.cancelEvent })).toHaveCount(0);
    });

    test('Command Staff see Edit and Copy on the event page; members do not', async ({ page, browser }) => {
        await page.goto('/events');
        await page.getByRole('link', { name: seeded.commandEvent }).first().click();
        await expect(page.getByRole('heading', { name: seeded.commandEvent })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Edit' }).first()).toBeVisible();

        const member = await browser.newContext({ storageState: personas.member.storageState });
        const memberPage = await member.newPage();
        await memberPage.goto(page.url());
        await expect(memberPage.getByRole('heading', { name: seeded.commandEvent })).toBeVisible();
        await expect(memberPage.getByRole('link', { name: 'Edit' })).toHaveCount(0);
        await member.close();
    });
});

test.describe('shifts and charity', () => {
    test('a moderator adds a shift, which saves once its times are in', async ({ page }) => {
        await openAdmin(page, seeded.commandEvent, 'shifts');

        const before = await page.locator('input[name$="[starts_at]"]').count();
        await page.getByRole('button', { name: 'Add Shift' }).click();
        await expect(page.locator('input[name$="[starts_at]"]')).toHaveCount(before + 1);

        await fillAndLeave(page.locator('input[name$="[starts_at]"]').last(), '15:00');
        await saved(page, () => fillAndLeave(page.locator('input[name$="[ends_at]"]').last(), '18:00'));

        await page.reload();
        await expect(page.locator('input[name$="[starts_at]"]')).toHaveCount(before + 1);
    });

    test('a moderator adds a station to a shift and removes it', async ({ page }) => {
        await openAdmin(page, seeded.commandEvent, 'shifts');

        const first = page.locator('tr').filter({ has: page.getByRole('button', { name: 'Stations' }) }).first();
        await first.getByRole('button', { name: 'Stations' }).click();

        await fillAndLeave(page.getByLabel('Station name').last(), 'E2E Photo Line');
        await saved(page, () => fillAndLeave(page.getByLabel('Capacity').last(), '3'));

        await page.reload();
        await expect(page.getByLabel('Station name').first()).toHaveValue('E2E Photo Line');

        await page.getByRole('button', { name: 'Remove E2E Photo Line' }).click();
        await confirmDialog(page, 'Remove');

        await page.reload();
        await expect(page.getByLabel('Station name')).toHaveCount(0);
    });

    test('a moderator records charity raised on a shift', async ({ page }) => {
        await openAdmin(page, seeded.commandEvent, 'charity');

        await saved(page, () => fillAndLeave(page.locator('input[name$="[charity_name]"]').first(), 'E2E Children\'s Hospital'));
        await saved(page, () => fillAndLeave(page.locator('input[name$="[charity_hours]"]').first(), '4'));
        await saved(page, () => fillAndLeave(page.locator('input[name$="[charity_direct_funds]"]').first(), '250'));
        await expect(page.getByText('Saved').first()).toBeVisible();

        await page.reload();
        await expect(page.locator('input[name$="[charity_name]"]').first()).toHaveValue('E2E Children\'s Hospital');
        await expect(page.locator('input[name$="[charity_direct_funds]"]').first()).toHaveValue(/^250/);
    });
});

test.describe('reaching the troopers', () => {
    test('a moderator broadcasts a message, and a trooper going gets it', async ({ page, browser }) => {
        await page.goto('/events');
        await page.getByRole('link', { name: seeded.commandEvent }).first().click();

        const broadcast = page.locator('#broadcast-message');
        await broadcast.locator('textarea[name="message"]').fill('E2E broadcast: staging moved to dock B.');
        await broadcast.getByRole('button', { name: 'Send Message' }).click();
        await expect(broadcast.locator('textarea[name="message"]')).toHaveValue('');

        const trooper = await browser.newContext({ storageState: { cookies: [], origins: [] } });
        const inbox = await trooper.newPage();
        await signInAndLeaveLogin(inbox, seeded.rostered.email);
        await inbox.goto('/account/push-notifications');
        await expect(inbox.locator('main, .main-content').getByText('E2E broadcast: staging moved to dock B.').first()).toBeVisible();
        await trooper.close();
    });

    test('a moderator shares a read-only roster link with an event coordinator', async ({ page, browser }) => {
        await page.goto('/events');
        await page.getByRole('link', { name: seeded.commandEvent }).first().click();

        const share = page.locator('#share-roster');
        await share.locator('input[name="recipient_email"]').fill('coordinator@e2e.test');
        await share.getByRole('button', { name: 'Send Roster Link' }).click();
        await expect(share.getByText('Roster successfully sent.')).toBeVisible();

        // The link goes out by email; read it back the way the email builds it.
        const url = tinker(`
            $share = App\\Models\\EventShare::where('recipient_email', 'coordinator@e2e.test')->latest('id')->firstOrFail();
            echo route('shares.roster', $share);
        `);

        const guest = await browser.newContext({ storageState: { cookies: [], origins: [] } });
        const roster = await guest.newPage();
        await roster.goto(url);
        await expect(roster.getByText(seeded.rostered.name).first()).toBeVisible();
        await guest.close();
    });
});

test.describe('the roster', () => {
    test('a moderator adds a trooper to a shift, and removes them again', async ({ page }) => {
        await openAdmin(page, seeded.commandEvent, 'roster');
        await expandShifts(page);

        await page.getByLabel('Add Trooper').first().click();
        const picker = page.getByRole('dialog');
        await picker.getByRole('textbox').fill(seeded.friend.name);
        await picker.getByRole('button', { name: new RegExp(seeded.friend.name) }).first().click();

        await saved(page, () => page.getByRole('button', { name: 'Add to Roster' }).click());

        const row = page.getByRole('row').filter({ has: page.getByRole('link', { name: seeded.friend.name }) });
        await expect(row).toBeVisible();

        await row.getByRole('button', { name: `Remove ${seeded.friend.name} from the roster` }).click();
        await confirmDialog(page, 'Remove');
        await expect(page.getByText(`${seeded.friend.name} was removed from the roster.`)).toBeVisible();
        await expect(row).toHaveCount(0);
    });

    test('a moderator changes a rostered trooper\'s status, which saves right away', async ({ page }) => {
        await openAdmin(page, seeded.commandEvent, 'roster');
        await expandShifts(page);

        const status = page.locator('select[name^="troopers["][name$="[status]"]').first();
        await saved(page, () => status.selectOption('standby'));

        await page.reload();
        await expandShifts(page);
        await expect(page.locator('select[name^="troopers["][name$="[status]"]').first()).toHaveValue('standby');
    });

    test('a moderator downloads the roster', async ({ page }) => {
        await page.goto('/events');
        await page.getByRole('link', { name: seeded.commandEvent }).first().click();

        const response = await page.request.get(page.url().replace(/\/$/, '') + '/roster-csv');

        expect(response.status()).toBe(200);
        expect(await response.text()).toContain(seeded.rostered.name);
    });
});

test.describe('uploads', () => {
    test('a moderator uploads an instructional photo, sends it to the mission review, then deletes it', async ({ page }) => {
        await openAdmin(page, seeded.commandEvent, 'uploads');

        await saved(page, () => page.locator('input[type="file"][name="images[]"]').setInputFiles({
            name: 'e2e-admin.png',
            mimeType: 'image/png',
            buffer: PNG,
        }));
        const card = page.locator('.col[id^="upload-"]').first();
        await expect(card).toBeVisible();

        await card.getByRole('button', { name: 'Send to Mission Review' }).click();
        await confirmDialog(page, 'Send to Mission Review');
        await expect(page.locator('.col[id^="upload-"]')).toHaveCount(0);

        await page.locator('#app button.nav-link').filter({ hasText: 'Mission Review' }).click();
        const photo = page.locator('.col[id^="upload-"]').first();
        await expect(photo).toBeVisible();

        await photo.getByRole('button', { name: 'Delete' }).click();
        await confirmDialog(page, 'Delete');
        await expect(page.locator('.col[id^="upload-"]')).toHaveCount(0);
    });
});

// A 1x1 transparent PNG.
const PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
    'base64',
);
