// Command Staff looking after troopers: finding them, editing their profile, role, club
// memberships and guardian, marking them created in error or R.I.P., flagging them do not
// trust, and adding a trooper to a unit's roster.
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';

// Finds the trooper by their legal name (shown on a line of its own in the list) so that
// "E2E Trooper" doesn't also match "E2E Trooper Friend".
async function openTrooper(page, name, tab = null) {
    await page.goto(`/admin/troopers?search_term=${encodeURIComponent(name)}`);
    const row = page.getByRole('row').filter({ has: page.getByText(name, { exact: true }) });
    const href = await row.locator('a[href*="/admin/troopers/"]').last().getAttribute('href');
    await page.goto(tab ? `${href}?tab=${tab}` : href);

    return href;
}

function tab(page, name) {
    return page.locator('#app button.nav-link').filter({ hasText: name });
}

async function saved(page, action) {
    const response = page.waitForResponse((r) => r.url().includes('/admin/troopers/') && r.request().method() !== 'GET');
    await action();
    await response;
    await page.waitForLoadState('networkidle');
}

async function selectLike(select, text) {
    await select.selectOption(await select.locator('option', { hasText: text }).first().getAttribute('value'));
}

function card(page, name) {
    return page.locator('form').filter({ has: page.locator('.card-header', { hasText: name }) });
}

test.describe('as a moderator', () => {
    test.use({ storageState: personas.moderator.storageState });

    test('finds troopers by name or email, and by role', async ({ page }) => {
        await page.goto('/admin/troopers?search_term=e2e-profile-subject%40e2e.test');
        await expect(page.getByRole('row').filter({ hasText: seeded.profileSubject })).toBeVisible();
        await expect(page.getByRole('row').filter({ hasText: personas.member.name })).toHaveCount(0);

        await page.goto('/admin/troopers');
        await page.getByRole('link', { name: 'Moderator', exact: true }).first().click();
        await expect(page.getByRole('row').filter({ hasText: personas.moderator.name })).toBeVisible();
        await expect(page.getByRole('row').filter({ hasText: personas.member.name })).toHaveCount(0);
    });

    test('edits a trooper\'s profile, and the change shows in their change log', async ({ page }) => {
        const profile = await openTrooper(page, seeded.profileSubject);
        await expect(tab(page, 'Authority')).toHaveCount(0);

        await page.getByLabel('Legal Name').fill('E2E Profile Subject Legal');
        await page.getByLabel('Phone').fill('5550123456');
        await page.getByLabel('Status').selectOption({ label: 'Reserve' });
        await saved(page, () => page.getByRole('button', { name: 'Update', exact: true }).click());

        await page.goto(profile);
        await expect(page.getByLabel('Legal Name')).toHaveValue('E2E Profile Subject Legal');
        await expect(page.getByLabel('Phone')).toHaveValue(/555.*012.*3456/);
        await expect(page.getByLabel('Status')).toHaveValue('reserve');

        await tab(page, 'Changes').click();
        const change = page.getByRole('row').filter({ hasText: 'membership_status' });
        await expect(change).toContainText('reserve');
        await expect(change).toContainText(personas.moderator.name);
    });

    test('a profile needs a legal name and an email no one else uses', async ({ page }) => {
        await openTrooper(page, seeded.authoritySubject);

        await page.getByLabel('Legal Name').fill('');
        await page.getByLabel('Email').fill(personas.member.email);
        await page.getByRole('button', { name: 'Update', exact: true }).click();

        await expect(page.locator('.text-danger').getByText('The legal name field is required.')).toBeVisible();
        await expect(page.locator('.text-danger').getByText('The email has already been taken.')).toBeVisible();
    });

    test('cannot open the profile of a trooper outside their clubs', async ({ page }) => {
        await page.goto(`/admin/troopers?search_term=${encodeURIComponent(personas.admin.name)}`);
        await expect(page.getByText(personas.admin.email)).toHaveCount(0);

        const response = await page.goto(`/admin/troopers/${seeded.adminId}`);
        expect(response.status()).toBe(403);
    });

    test('changes a trooper\'s club membership, unit and member ID, then removes it', async ({ page }) => {
        const membership = await openTrooper(page, seeded.membershipSubject.name, 'memberships');
        const legion_card = card(page, '501st Legion');
        await expect(legion_card.getByLabel('Identifier')).toHaveValue('91111');

        await legion_card.getByLabel('Identifier').fill('92222');
        await legion_card.getByLabel('Status').selectOption({ label: 'Reserve' });
        await selectLike(legion_card.getByLabel('Member Of'), 'E2E Squad');
        await saved(page, () => legion_card.getByRole('button', { name: 'Update' }).click());

        await page.goto(`${membership}?tab=memberships`);
        await expect(legion_card.getByLabel('Identifier')).toHaveValue('92222');
        await expect(legion_card.getByLabel('Status')).toHaveValue('reserve');
        await expect(legion_card.getByLabel('Member Of').locator('option:checked')).toContainText('E2E Squad');

        page.once('dialog', (dialog) => dialog.accept());
        await saved(page, () => legion_card.getByRole('button', { name: 'Remove Membership' }).click());
        await page.goto(`${membership}?tab=memberships`);
        await expect(legion_card.getByLabel('Identifier')).toHaveValue('');
    });

    test('sees a trooper\'s costumes', async ({ page }) => {
        await openTrooper(page, personas.trooper.name, 'costumes');
        await expect(page.getByText(seeded.trooperCostume).first()).toBeVisible();

    });

    test('adds a trooper to a unit\'s roster', async ({ page }) => {
        await page.goto('/admin/troopers');
        await page.getByRole('link', { name: 'Recruit Trooper' }).click();

        await page.locator('[name="picker-trooper_id"]').click();
        const picker = page.getByRole('dialog');
        await picker.getByRole('textbox').fill(seeded.recruitTarget);
        await picker.getByRole('listitem').filter({ hasText: seeded.recruitTarget }).click();

        await page.locator('#organization_id').selectOption({ label: '— — — E2E Squad' });
        await page.locator('#identifier').fill('95555');
        await page.getByRole('button', { name: 'Add to Roster' }).click();

        await openTrooper(page, seeded.recruitTarget, 'memberships');
        const legion_card = card(page, '501st Legion');
        await expect(legion_card.getByLabel('Identifier')).toHaveValue('95555');
        await expect(legion_card.getByLabel('Member Of').locator('option:checked')).toContainText('E2E Squad');
    });
});

test.describe('as an administrator', () => {
    test.use({ storageState: personas.admin.storageState });

    test('makes a trooper a moderator of a garrison', async ({ page }) => {
        await openTrooper(page, seeded.authoritySubject, 'authority');

        await page.getByLabel('Role').selectOption({ label: 'Moderator' });
        await page.getByRole('row').filter({ hasText: 'E2E Garrison' }).getByRole('checkbox').check();
        await expect(page.getByRole('row').filter({ hasText: 'E2E Squad' }).getByRole('checkbox')).toBeChecked();
        await saved(page, () => page.getByRole('button', { name: 'Update Authority' }).click());

        await openTrooper(page, seeded.authoritySubject, 'authority');
        await expect(page.getByLabel('Role')).toHaveValue('moderator');
        await expect(page.getByRole('row').filter({ hasText: 'E2E Garrison' }).getByRole('checkbox')).toBeChecked();

        await page.goto(`/admin/troopers?search_term=${encodeURIComponent(seeded.authoritySubject)}`);
        await expect(page.getByRole('row').filter({ hasText: seeded.authoritySubject })).toContainText('Moderator');
    });

    test('sees the most recent troop a trooper attended for each club', async ({ page }) => {
        await openTrooper(page, personas.member.name, 'events');
        await expect(page.getByRole('row').filter({ hasText: '501st Legion' })).toContainText(seeded.pastUncountedEvent);
    });

    test('a cadet under 18 needs an adult guardian who uses the tracker', async ({ page }) => {
        await openTrooper(page, seeded.cadet, 'guardian');

        const twelve_years_ago = new Date();
        twelve_years_ago.setFullYear(twelve_years_ago.getFullYear() - 12);
        // The date field is a read-only date picker.
        await page.getByLabel('Date of Birth').evaluate((input, date) => input._flatpickr.setDate(date, true),
            twelve_years_ago.toISOString().slice(0, 10));
        await page.getByRole('button', { name: 'Update Guardian' }).click();
        await expect(page.locator('.text-danger').getByText(/guardian email field is required/i)).toBeVisible();

        await page.getByLabel('Guardian Email').fill('nobody@e2e.test');
        await page.getByRole('button', { name: 'Update Guardian' }).click();
        await expect(page.locator('.text-danger').getByText(/guardian email is invalid/i)).toBeVisible();

        await page.getByLabel('Guardian Email').fill(seeded.guardian.email);
        await saved(page, () => page.getByRole('button', { name: 'Update Guardian' }).click());

        await openTrooper(page, seeded.cadet, 'guardian');
        await expect(page.getByLabel('Guardian Email')).toHaveValue(seeded.guardian.email);
    });

    test('marks one trooper created in error and another R.I.P.', async ({ page }) => {
        await openTrooper(page, seeded.voided);
        await page.getByLabel('Status').selectOption({ label: 'Invalid' });
        await saved(page, () => page.getByRole('button', { name: 'Update', exact: true }).click());
        await page.goto(`/admin/troopers?search_term=${encodeURIComponent(seeded.voided)}`);
        await expect(page.getByRole('row').filter({ hasText: seeded.voided })).toContainText('Created in Error');

        await openTrooper(page, seeded.departed);
        await page.getByLabel('Status').selectOption({ label: 'Departed' });
        await saved(page, () => page.getByRole('button', { name: 'Update', exact: true }).click());

        await openTrooper(page, seeded.departed);
        await expect(page.getByText('This trooper is departed (R.I.P.).')).toBeVisible();
    });

    test('flags a trooper do not trust with a reason, then clears it', async ({ page }) => {
        await openTrooper(page, seeded.untrusted);

        await page.getByLabel('Reason').fill('E2E: confirmed troops they missed.');
        await saved(page, () => page.getByRole('button', { name: 'Mark Untrusted' }).click());

        await expect(page.getByText('Untrusted: E2E: confirmed troops they missed.')).toBeVisible();

        await saved(page, () => page.getByRole('button', { name: 'Clear Untrusted' }).click());
        await expect(page.getByText(/^Untrusted:/)).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Mark Untrusted' })).toBeVisible();
    });
});
