// What Command Staff publish: awards (and who earned them), notices, the organization tree
// and its logos, and the FAQ.
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';

// A 1x1 transparent PNG.
const PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
    'base64',
);

function form(page) {
    return page.locator('main form, .main-content form').last();
}

// The Svelte organization picker opens a searchable list of organizations.
async function pickOrganization(page, name) {
    await page.getByLabel('Organization', { exact: true }).click();
    const picker = page.getByRole('dialog');
    await picker.getByRole('textbox').fill(name);
    await picker.getByRole('button', { name, exact: true }).click();
}

// The Svelte trooper picker searches as you type.
async function pickTrooper(page, name) {
    await page.getByLabel('Trooper', { exact: true }).click();
    const picker = page.getByRole('dialog');
    await picker.getByRole('textbox').fill(name);
    await picker.getByRole('button').filter({ hasText: name }).first().click();
}

async function typeMarkdown(page, text) {
    await page.locator('.CodeMirror').first().click();
    await page.keyboard.type(text);
}

// Date-time fields take typed text ("2030-06-01 1:00PM") as well as the calendar popup.
async function setDate(page, field, date) {
    const pad = (n) => String(n).padStart(2, '0');
    const hour = date.getHours() % 12 || 12;
    const text = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} `
        + `${hour}:${pad(date.getMinutes())}${date.getHours() >= 12 ? 'PM' : 'AM'}`;
    const input = typeof field === 'string' ? page.locator(field) : field;
    await input.fill(text);
}

function rowFor(page, text) {
    return page.getByRole('row').filter({ hasText: text });
}

test.describe('awards', () => {
    test.use({ storageState: personas.moderator.storageState });

    test('a moderator creates an award, gives it to a trooper, then takes it back', async ({ page }) => {
        await page.goto('/admin/awards');
        await page.locator('a[href$="/admin/awards/create"]').click();

        await page.getByRole('button', { name: 'Create', exact: true }).click();
        await expect(form(page).getByText('The name field is required.')).toBeVisible();

        await pickOrganization(page, '501st Legion');
        await page.getByLabel('Frequency').selectOption({ label: 'Annually' });
        await page.getByLabel('Name').fill(seeded.newAward);
        await page.getByRole('button', { name: 'Create', exact: true }).click();
        await expect(page).toHaveURL(/\/admin\/awards\/\d+\/edit$/);

        await page.getByLabel('Name').fill(`${seeded.newAward} 2026`);
        await page.getByRole('button', { name: 'Update', exact: true }).click();
        await expect(page).toHaveURL(/\/admin\/awards$/);
        await page.goto(`/admin/awards?search_term=${encodeURIComponent(seeded.newAward)}`);
        await expect(rowFor(page, `${seeded.newAward} 2026`)).toContainText('501st Legion');

        const award = await rowFor(page, seeded.newAward).locator('a[href$="/edit"]').first().getAttribute('href');
        await page.goto(award.replace(/\/edit$/, '/troopers'));
        await page.getByRole('link', { name: 'Award Trooper' }).click();
        await pickTrooper(page, personas.trooper.name);
        await page.getByRole('button', { name: 'Submit' }).click();

        await expect(page).toHaveURL(/\/troopers$/);
        const awarded = rowFor(page, personas.trooper.name);
        await expect(awarded).toBeVisible();

        await awarded.getByRole('button', { name: 'Remove' }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Remove' }).click();
        await expect(rowFor(page, personas.trooper.name)).toHaveCount(0);
    });
});

test.describe('notices', () => {
    test.use({ storageState: personas.moderator.storageState });

    test('a moderator posts a notice to a garrison, copies it for later, and edits the copy', async ({ page }) => {
        const today = new Date();
        const next_week = new Date(Date.now() + 7 * 86400000);
        const next_month = new Date(Date.now() + 30 * 86400000);

        await page.goto('/admin/notices/create');
        await pickOrganization(page, 'E2E Garrison');
        await page.getByLabel('Type').selectOption({ index: 2 });
        await page.getByLabel('Title').fill(seeded.newNotice);
        await setDate(page, page.getByLabel('Starts'), today);
        await setDate(page, page.getByLabel('Ends'), next_week);
        await typeMarkdown(page, 'Muster at the north gate.');
        await page.getByRole('button', { name: 'Create', exact: true }).click();
        await expect(page).toHaveURL(/\/admin\/notices$/);

        await page.goto('/admin/notices?scope=active');
        const posted = rowFor(page, seeded.newNotice);
        await expect(posted).toContainText('E2E Garrison');

        await page.goto(await posted.locator('a[href*="copy_id="]').getAttribute('href'));
        await expect(page.getByLabel('Title')).toHaveValue(`${seeded.newNotice} (Copy)`);
        await page.getByLabel('Title').fill(`${seeded.newNotice} (next month)`);
        await setDate(page, page.getByLabel('Starts'), next_week);
        await setDate(page, page.getByLabel('Ends'), next_month);
        await page.getByRole('button', { name: 'Create', exact: true }).click();
        await expect(page).toHaveURL(/\/admin\/notices$/);

        await page.goto('/admin/notices?scope=future');
        const copy = rowFor(page, `${seeded.newNotice} (next month)`);
        await page.goto(await copy.locator('a[href$="/edit"]').getAttribute('href'));
        await page.getByLabel('Title').fill(`${seeded.newNotice} (rescheduled)`);
        await page.getByRole('button', { name: 'Update', exact: true }).click();
        await expect(page).toHaveURL(/\/admin\/notices$/);

        await page.goto('/admin/notices?scope=future');
        await expect(rowFor(page, `${seeded.newNotice} (rescheduled)`)).toBeVisible();
        await page.goto('/admin/notices?scope=active');
        await expect(rowFor(page, '(rescheduled)')).toHaveCount(0);
    });
});

test.describe('organizations', () => {
    test.use({ storageState: personas.admin.storageState });

    test('an administrator adds a unit under a garrison, renames it, and gives it a logo', async ({ page }) => {
        await page.goto('/admin/organizations');
        const garrison = rowFor(page, 'E2E Garrison').first();
        await page.goto(await garrison.locator('a[href$="/create"]').getAttribute('href'));
        await expect(page.getByLabel('Parent')).toHaveValue('E2E Garrison');

        await page.getByRole('button', { name: 'Create', exact: true }).click();
        await expect(form(page).getByText('The name field is required.')).toBeVisible();

        await page.getByLabel('Name').fill(seeded.newUnit);
        await page.getByRole('button', { name: 'Create', exact: true }).click();
        await expect(page).toHaveURL(/\/admin\/organizations\/\d+\/edit$/);

        await page.goto('/admin/organizations');
        const unit = rowFor(page, seeded.newUnit);
        await page.goto(await unit.locator('a[href$="/edit"]').getAttribute('href'));
        await page.getByLabel('Name').fill(`${seeded.newUnit} Prime`);
        await page.getByRole('button', { name: 'Update', exact: true }).click();
        await expect(page).toHaveURL(/\/admin\/organizations$/);

        await page.goto('/admin/organizations');
        await page.goto(await rowFor(page, `${seeded.newUnit} Prime`).locator('a[href$="/edit"]').getAttribute('href'));

        const logo = page.getByAltText('Organization Logo');
        const before = await logo.getAttribute('src');
        await page.getByTestId('logo-input').setInputFiles({ name: 'logo.png', mimeType: 'image/png', buffer: PNG });
        await expect(page.getByAltText('Organization Logo')).not.toHaveAttribute('src', before);
    });
});

test.describe('FAQ', () => {
    test.use({ storageState: personas.admin.storageState });

    test('an administrator writes a FAQ section and question, troopers read it, then it is removed', async ({ page, browser }) => {
        await page.goto('/admin/faq/sections/create');
        await page.getByLabel('Label').fill(seeded.faqSection);
        await page.getByLabel('Icon').fill('fa-star');
        await page.getByRole('button', { name: 'Create', exact: true }).click();
        await expect(page).toHaveURL(/\/admin\/faq\/sections\/\d+\/edit$/);

        await page.goto('/admin/faq');
        const section = rowFor(page, seeded.faqSection);
        await section.getByRole('button', { name: 'Toggle section items' }).click();
        await page.locator('a[href*="/items/create"]').first().click();

        await page.getByLabel('Title').fill(seeded.faqQuestion);
        await typeMarkdown(page, 'Bring water and a **cooling vest**.');
        await page.getByRole('button', { name: 'Create', exact: true }).click();
        await expect(page).toHaveURL(/\/items\/\d+\/edit$/);

        const member = await browser.newContext({ storageState: personas.member.storageState });
        const reader = await member.newPage();
        await reader.goto('/faq');
        await expect(reader.getByText(seeded.faqSection).first()).toBeVisible();
        await reader.getByText(seeded.faqQuestion).first().click();
        await expect(reader.getByText('cooling vest').first()).toBeVisible();

        await page.goto('/admin/faq');
        await rowFor(page, seeded.faqSection).getByRole('button', { name: 'Toggle section items' }).click();
        const item = rowFor(page, seeded.faqQuestion);
        await item.getByRole('button', { name: 'Actions' }).click();
        await item.getByRole('button', { name: 'Delete' }).click();
        await page.locator('.modal.show').getByRole('button', { name: 'Delete' }).click();
        await expect(rowFor(page, seeded.faqQuestion)).toHaveCount(0);

        // A section's actions only show while its questions are folded away.
        await page.goto('/admin/faq');
        const emptied = rowFor(page, seeded.faqSection);
        await emptied.getByRole('button', { name: 'Actions' }).click();
        await emptied.getByRole('button', { name: 'Delete' }).click();
        await page.locator('.modal.show').getByRole('button', { name: 'Delete' }).click();
        await expect(rowFor(page, seeded.faqSection)).toHaveCount(0);

        await reader.goto('/faq');
        await expect(reader.getByText(seeded.faqSection)).toHaveCount(0);
        await member.close();
    });
});
