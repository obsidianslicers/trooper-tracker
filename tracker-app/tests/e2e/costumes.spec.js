// Costumes and the credit they earn: Command Staff create, rename and delete costumes; a
// moderator reviews costumes troopers added themselves and revokes one; and a do-not-trust
// trooper's troops wait for a moderator before they count.
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';
import { signInAndLeaveLogin } from './support/auth.js';

function form(page) {
    return page.locator('main form, .main-content form').last();
}

function costumeRow(page, name) {
    return page.getByRole('row').filter({ hasText: name });
}

async function searchCostumes(page, name) {
    await page.goto(`/admin/costumes?search_term=${encodeURIComponent(name)}`);
}

// The row's Update and Delete links sit in a closed action menu.
async function openCostume(page, name, action) {
    await searchCostumes(page, name);
    await page.goto(await costumeRow(page, name).locator(`a[href$="/${action}"]`).getAttribute('href'));
}

async function ownerCostumes(page) {
    await page.goto(`/admin/troopers?search_term=${encodeURIComponent(seeded.costumeOwner)}`);
    const href = await page.getByRole('row').filter({ hasText: seeded.costumeOwner })
        .locator('a[href*="/admin/troopers/"]').last().getAttribute('href');
    await page.goto(`${href}/costumes`);
}

test.describe('costume list', () => {
    test.use({ storageState: personas.admin.storageState });

    test('an administrator creates a costume and gives it to a club', async ({ page }) => {
        await page.goto('/admin/costumes');
        await page.getByRole('link', { name: /Costume/ }).filter({ has: page.locator('i') }).first().click();
        await expect(page).toHaveURL(/\/admin\/costumes\/create$/);

        await page.getByRole('button', { name: 'Create' }).click();
        await expect(form(page).getByText('The name field is required.')).toBeVisible();

        await page.locator('[name="name"]').fill(seeded.newCostume);
        await page.getByRole('button', { name: 'Create' }).click();
        await expect(page).toHaveURL(/\/admin\/costumes\/\d+\/update$/);

        await page.getByLabel('Rebel Legion').check();
        await page.getByRole('button', { name: 'Update' }).click();

        await searchCostumes(page, seeded.newCostume);
        await expect(costumeRow(page, seeded.newCostume)).toContainText('Rebel Legion');
    });

    test('an administrator renames a costume; the new name must be unused', async ({ page }) => {
        await openCostume(page, seeded.renamedCostume, 'update');

        await page.locator('[name="name"]').fill(seeded.keptCostume);
        await page.getByRole('button', { name: 'Update' }).click();
        await expect(form(page).getByText('The name has already been taken.')).toBeVisible();

        await page.locator('[name="name"]').fill(`${seeded.renamedCostume} Mk II`);
        await page.getByRole('button', { name: 'Update' }).click();

        await searchCostumes(page, `${seeded.renamedCostume} Mk II`);
        await expect(costumeRow(page, `${seeded.renamedCostume} Mk II`)).toBeVisible();
    });

    test('deleting a costume warns that troopers will lose it', async ({ page }) => {
        await openCostume(page, seeded.doomedCostume, 'delete');

        await expect(page.getByText('1 trooper costume will be removed from trooper profiles.')).toBeVisible();
        await page.getByRole('button', { name: 'Delete' }).click();

        await searchCostumes(page, seeded.doomedCostume);
        await expect(costumeRow(page, seeded.doomedCostume)).toHaveCount(0);
    });
});

test.describe('costume review', () => {
    test.use({ storageState: personas.moderator.storageState });

    test('a moderator approves one costume a trooper added and denies another', async ({ page }) => {
        await page.goto('/admin/troopers/costume-approvals');

        const pending = page.getByRole('row').filter({ hasText: seeded.pendingCostume });
        await expect(pending).toContainText(seeded.costumeOwner);
        await pending.getByRole('button', { name: 'Approve' }).click();

        await page.getByRole('row').filter({ hasText: seeded.refusedCostume })
            .getByRole('button', { name: 'Deny' }).click();

        await page.goto('/admin/troopers/costume-approvals');
        await expect(page.getByRole('row').filter({ hasText: seeded.pendingCostume })).toHaveCount(0);
        await expect(page.getByRole('row').filter({ hasText: seeded.refusedCostume })).toHaveCount(0);

        await ownerCostumes(page);
        await expect(page.getByRole('row').filter({ hasText: seeded.pendingCostume })
            .getByRole('button', { name: 'Revoke' })).toBeVisible();
        await expect(page.getByRole('row').filter({ hasText: seeded.refusedCostume })).toHaveCount(0);
    });

    test('a moderator revokes a costume, then undoes it', async ({ page }) => {
        await ownerCostumes(page);

        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('row').filter({ hasText: seeded.revokedCostume })
            .getByRole('button', { name: 'Revoke' }).click();
        await expect(page.getByRole('row').filter({ hasText: seeded.revokedCostume })
            .getByRole('button', { name: 'Undo' })).toBeVisible();

        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('row').filter({ hasText: seeded.revokedCostume })
            .getByRole('button', { name: 'Undo' }).click();
        await expect(page.getByRole('row').filter({ hasText: seeded.revokedCostume })
            .getByRole('button', { name: 'Revoke' })).toBeVisible();
    });
});

test.describe('troop verification', () => {
    async function reportAttendance(page, event) {
        await page.goto('/events/closed');
        await page.getByRole('link', { name: event }).first().click();
        await page.locator('[id^="shift-container-"]').first().getByRole('link', { name: 'Made It' }).click();
        await expect(page.getByText('A moderator will confirm this troop')).toBeVisible();
    }

    test('a do-not-trust trooper\'s troops wait for a moderator, who approves one and removes another', async ({ browser }) => {
        const trooper_context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
        const trooper = await trooper_context.newPage();
        await signInAndLeaveLogin(trooper, seeded.untrustedTrooper.email);
        await reportAttendance(trooper, seeded.verifiedParade);
        await reportAttendance(trooper, seeded.disputedParade);

        const moderator_context = await browser.newContext({ storageState: personas.moderator.storageState });
        const moderator = await moderator_context.newPage();
        await moderator.goto('/admin/troopers/troop-verifications');

        const verified = moderator.getByRole('row').filter({ hasText: seeded.verifiedParade });
        await expect(verified).toContainText(seeded.untrustedTrooper.name);
        await expect(verified).toContainText(seeded.untrustedCostume);
        await verified.getByRole('button', { name: 'Approve' }).click();

        await moderator.getByRole('row').filter({ hasText: seeded.disputedParade })
            .getByRole('button', { name: 'Remove' }).click();

        await moderator.goto('/admin/troopers/troop-verifications');
        await expect(moderator.getByText('No troops are waiting for verification.')).toBeVisible();

        await trooper.goto('/events/closed');
        await trooper.getByRole('link', { name: seeded.verifiedParade }).first().click();
        await expect(trooper.locator('[id^="shift-container-"]').first().getByText('Attended')).toBeVisible();

        await trooper_context.close();
        await moderator_context.close();
    });
});
