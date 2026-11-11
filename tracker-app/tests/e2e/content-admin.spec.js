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

// Blade's organization and trooper pickers open a modal list to pick from.
async function pick(page, property, name, search = false) {
    await page.locator(`[name="picker-${property}"]`).click();
    const picker = page.getByRole('dialog');
    if (search) {
        await picker.getByRole('textbox').fill(name);
    }
    await picker.getByRole('listitem').filter({ hasText: name }).first().click();
}

// Markdown fields use an editor in place of the textarea.
async function typeMarkdown(page, text) {
    await page.locator('.CodeMirror').first().click();
    await page.keyboard.type(text);
}

async function setDate(page, selector, date) {
    await page.locator(selector).evaluate((input, value) => input._flatpickr.setDate(value, true), date);
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

        await pick(page, 'organization_id', '501st Legion');
        await page.locator('#frequency').selectOption({ label: 'Annually' });
        await page.locator('#name').fill(seeded.newAward);
        await page.getByRole('button', { name: 'Create', exact: true }).click();
        await expect(page).toHaveURL(/\/admin\/awards\/\d+\/update$/);

        await page.locator('#name').fill(`${seeded.newAward} 2026`);
        await page.getByRole('button', { name: 'Update', exact: true }).click();
        await page.goto(`/admin/awards?search_term=${encodeURIComponent(seeded.newAward)}`);
        await expect(rowFor(page, `${seeded.newAward} 2026`)).toContainText('501st Legion');

        const award = await rowFor(page, seeded.newAward).locator('a[href$="/update"]').first().getAttribute('href');
        await page.goto(award.replace(/\/update$/, '/troopers'));
        await page.getByRole('link', { name: 'Award Trooper' }).click();
        await pick(page, 'trooper_id', personas.trooper.name, true);
        await page.getByRole('button', { name: 'Submit' }).click();

        await expect(page).toHaveURL(/\/troopers$/);
        const awarded = rowFor(page, personas.trooper.name);
        await expect(awarded).toBeVisible();

        page.once('dialog', (dialog) => dialog.accept());
        await awarded.getByRole('button').last().click();
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
        await pick(page, 'organization_id', 'E2E Garrison');
        await page.locator('#type').selectOption({ index: 2 });
        await page.locator('#title').fill(seeded.newNotice);
        await setDate(page, '#starts_at', today);
        await setDate(page, '#ends_at', next_week);
        await typeMarkdown(page, 'Muster at the north gate.');
        await page.getByRole('button', { name: 'Create', exact: true }).click();

        await page.goto('/admin/notices?scope=active');
        const posted = rowFor(page, seeded.newNotice);
        await expect(posted).toContainText('E2E Garrison');

        await page.goto(await posted.locator('a[href*="copy_id="]').getAttribute('href'));
        await expect(page.locator('#title')).toHaveValue(`${seeded.newNotice} (Copy)`);
        await page.locator('#title').fill(`${seeded.newNotice} (next month)`);
        await setDate(page, '#starts_at', next_week);
        await setDate(page, '#ends_at', next_month);
        await page.getByRole('button', { name: 'Create', exact: true }).click();

        await page.goto('/admin/notices?scope=future');
        const copy = rowFor(page, `${seeded.newNotice} (next month)`);
        await page.goto(await copy.locator('a[href$="/update"]').getAttribute('href'));
        await page.locator('#title').fill(`${seeded.newNotice} (rescheduled)`);
        await page.getByRole('button', { name: 'Update', exact: true }).click();

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
        await expect(page.locator('#parent_name')).toHaveValue('E2E Garrison');

        await page.getByRole('button', { name: 'Create', exact: true }).click();
        await expect(form(page).getByText('The name field is required.')).toBeVisible();

        await page.locator('#name').fill(seeded.newUnit);
        await page.getByRole('button', { name: 'Create', exact: true }).click();

        await page.goto('/admin/organizations');
        const unit = rowFor(page, seeded.newUnit);
        await page.goto(await unit.locator('a[href$="/update"]').getAttribute('href'));
        await page.locator('#name').fill(`${seeded.newUnit} Prime`);
        await page.getByRole('button', { name: 'Update', exact: true }).click();

        await page.goto('/admin/organizations');
        await page.goto(await rowFor(page, `${seeded.newUnit} Prime`).locator('a[href$="/update"]').getAttribute('href'));

        const logo = page.getByAltText('Organization Logo');
        const before = await logo.getAttribute('src');
        await page.locator('#logo-input').setInputFiles({ name: 'logo.png', mimeType: 'image/png', buffer: PNG });
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
        await expect(page).toHaveURL(/\/admin\/faq\/sections\/\d+\/update$/);

        await page.goto('/admin/faq/index');
        const section = rowFor(page, seeded.faqSection);
        await section.getByRole('button', { name: 'Toggle section items' }).click();
        await page.locator('a[href*="/items/create"]').first().click();

        await page.getByLabel('Title').fill(seeded.faqQuestion);
        await typeMarkdown(page, 'Bring water and a **cooling vest**.');
        await page.getByRole('button', { name: 'Create', exact: true }).click();
        await expect(page).toHaveURL(/\/items\/\d+\/update$/);

        const member = await browser.newContext({ storageState: personas.member.storageState });
        const reader = await member.newPage();
        await reader.goto('/faq');
        await expect(reader.getByText(seeded.faqSection).first()).toBeVisible();
        await reader.getByText(seeded.faqQuestion).first().click();
        await expect(reader.getByText('cooling vest').first()).toBeVisible();

        await page.goto('/admin/faq/index');
        await rowFor(page, seeded.faqSection).getByRole('button', { name: 'Toggle section items' }).click();
        const item = rowFor(page, seeded.faqQuestion);
        await item.getByRole('button', { name: 'Actions' }).click();
        await item.getByRole('button', { name: 'Delete' }).click();
        await page.locator('.modal.show').getByRole('button', { name: 'Delete' }).click();
        await expect(rowFor(page, seeded.faqQuestion)).toHaveCount(0);

        // A section's actions only show while its questions are folded away.
        await page.goto('/admin/faq/index');
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
