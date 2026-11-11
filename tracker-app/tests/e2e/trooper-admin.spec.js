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
    await page.goto(tab ? `${href}/${tab}` : href);

    return href;
}

function tab(page, name) {
    return page.locator('a.nav-link[href*="/admin/troopers/"]').filter({ hasText: name });
}

function form(page) {
    return page.locator('main form, .main-content form').last();
}

function field(page, name) {
    return page.locator(`[name="${name}"]`);
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

        await field(page, 'legal_name').fill('E2E Profile Subject Legal');
        await field(page, 'phone').fill('5550123456');
        await field(page, 'membership_status').selectOption({ label: 'Reserve' });
        await page.getByRole('button', { name: 'Update' }).click();
        await expect(page).toHaveURL(/\/admin\/troopers$/);

        await page.goto(profile);
        await expect(field(page, 'legal_name')).toHaveValue('E2E Profile Subject Legal');
        await expect(field(page, 'phone')).toHaveValue('(555) 012-3456');
        await expect(field(page, 'membership_status')).toHaveValue('reserve');

        await tab(page, 'Changes').click();
        const change = page.getByRole('row').filter({ hasText: 'membership_status' });
        await expect(change).toContainText('reserve');
        await expect(change).toContainText(personas.moderator.name);
    });

    test('a profile needs a legal name and an email no one else uses', async ({ page }) => {
        await openTrooper(page, seeded.authoritySubject);

        await field(page, 'legal_name').fill('');
        await field(page, 'email').fill(personas.member.email);
        await page.getByRole('button', { name: 'Update' }).click();

        await expect(form(page).getByText('The legal name field is required.')).toBeVisible();
        await expect(form(page).getByText('The email has already been taken.')).toBeVisible();
    });

    test('cannot open the profile of a trooper outside their clubs', async ({ page }) => {
        await page.goto(`/admin/troopers?search_term=${encodeURIComponent(personas.admin.name)}`);
        await expect(page.getByText(personas.admin.email)).toHaveCount(0);

        const response = await page.goto(`/admin/troopers/${seeded.adminId}`);
        expect(response.status()).toBe(403);
    });

    test('changes a trooper\'s club membership, unit and member ID, then removes it', async ({ page }) => {
        const legion = seeded.legionId;
        const membership = await openTrooper(page, seeded.membershipSubject.name, 'membership');
        await expect(field(page, `organizations[${legion}][identifier]`)).toHaveValue('91111');

        await field(page, `organizations[${legion}][identifier]`).fill('92222');
        await field(page, `organizations[${legion}][membership_status]`).selectOption({ label: 'Reserve' });
        await field(page, `picker-organizations[${legion}][assignment]`).click();
        await page.getByRole('dialog').getByRole('listitem').filter({ hasText: 'E2E Squad' }).click();
        await page.getByRole('button', { name: 'Update' }).click();

        await page.goto(`${membership}/membership`);
        await expect(field(page, `organizations[${legion}][identifier]`)).toHaveValue('92222');
        await expect(field(page, `organizations[${legion}][membership_status]`)).toHaveValue('reserve');
        await expect(field(page, `picker-organizations[${legion}][assignment]`)).toHaveValue(/E2E Squad/);

        await page.getByRole('button', { name: 'Remove 501st Legion membership' }).click();
        await page.goto(`${membership}/membership`);
        await expect(page.getByRole('button', { name: 'Remove 501st Legion membership' })).toHaveCount(0);
        await expect(field(page, `organizations[${legion}][identifier]`)).toHaveValue('');
    });

    test('sees a trooper\'s costumes', async ({ page }) => {
        await openTrooper(page, personas.trooper.name, 'costumes');
        await expect(page.getByText(seeded.trooperCostume).first()).toBeVisible();

    });

    test('adds a trooper to a unit\'s roster', async ({ page }) => {
        await page.goto('/admin/troopers');
        await page.getByRole('link', { name: 'Recruit Trooper' }).click();

        await field(page, 'picker-trooper_id').click();
        const picker = page.getByRole('dialog');
        await picker.getByRole('textbox').fill(seeded.recruitTarget);
        await picker.getByRole('listitem').filter({ hasText: seeded.recruitTarget }).click();

        await page.locator('#organization_id').selectOption({ label: '— — — E2E Squad' });
        await page.locator('#identifier').fill('95555');
        await page.getByRole('button', { name: 'Add to Roster' }).click();

        await openTrooper(page, seeded.recruitTarget, 'membership');
        await expect(field(page, `organizations[${seeded.legionId}][identifier]`)).toHaveValue('95555');
        await expect(field(page, `picker-organizations[${seeded.legionId}][assignment]`)).toHaveValue(/E2E Squad/);
    });
});

test.describe('as an administrator', () => {
    test.use({ storageState: personas.admin.storageState });

    test('makes a trooper a moderator of a garrison', async ({ page }) => {
        await openTrooper(page, seeded.authoritySubject, 'authority');

        await field(page, 'membership_role').selectOption({ label: 'Moderator' });
        await page.getByLabel('E2E Garrison').check();
        await expect(page.getByLabel('E2E Squad')).toBeChecked();
        await page.getByRole('button', { name: 'Update' }).first().click();

        await openTrooper(page, seeded.authoritySubject, 'authority');
        await expect(field(page, 'membership_role')).toHaveValue('moderator');
        await expect(page.getByLabel('E2E Garrison')).toBeChecked();

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
        await field(page, 'date_of_birth').evaluate((input, date) => input._flatpickr.setDate(date, true),
            twelve_years_ago.toISOString().slice(0, 10));
        await page.getByRole('button', { name: 'Update' }).click();
        await expect(form(page).getByText(/guardian email field is required/i)).toBeVisible();

        await field(page, 'guardian_email').fill('nobody@e2e.test');
        await page.getByRole('button', { name: 'Update' }).click();
        await expect(form(page).getByText(/guardian email is invalid/i)).toBeVisible();

        await field(page, 'guardian_email').fill(seeded.guardian.email);
        await page.getByRole('button', { name: 'Update' }).click();

        await openTrooper(page, seeded.cadet, 'guardian');
        await expect(field(page, 'guardian_email')).toHaveValue(seeded.guardian.email);
    });

    test('marks one trooper created in error and another R.I.P.', async ({ page }) => {
        await openTrooper(page, seeded.voided);
        await field(page, 'membership_status').selectOption({ label: 'Invalid' });
        await page.getByRole('button', { name: 'Update' }).click();
        await page.goto(`/admin/troopers?search_term=${encodeURIComponent(seeded.voided)}`);
        await expect(page.getByRole('row').filter({ hasText: seeded.voided })).toContainText('Created in Error');

        await openTrooper(page, seeded.departed);
        await field(page, 'membership_status').selectOption({ label: 'Departed' });
        await page.getByRole('button', { name: 'Update' }).click();

        await openTrooper(page, seeded.departed);
        await expect(page.getByText('This account is marked R.I.P. — In Memoriam.')).toBeVisible();
    });

    test('flags a trooper do not trust with a reason, then clears it', async ({ page }) => {
        await openTrooper(page, seeded.untrusted);

        await field(page, 'untrusted_reason').fill('E2E: confirmed troops they missed.');
        await page.getByRole('button', { name: 'Mark do not trust' }).click();

        const alert = page.getByRole('alert').filter({ hasText: 'Do not trust.' });
        await expect(alert).toContainText('E2E: confirmed troops they missed.');

        await page.getByRole('button', { name: 'Clear do not trust' }).click();
        await expect(page.getByRole('alert').filter({ hasText: 'Do not trust.' })).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Mark do not trust' })).toBeVisible();
    });
});
