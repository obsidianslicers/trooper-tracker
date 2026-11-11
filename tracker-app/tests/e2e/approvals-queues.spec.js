// The Command Staff queues: denying an application, approving and denying club join
// requests, and asking for a trooper's permissions to change (then approving or denying it).
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';
import { signInAndLeaveLogin } from './support/auth.js';

function card(page, name) {
    return page.locator('.card').filter({ hasText: name });
}

async function asTrooper(browser, email) {
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const page = await context.newPage();
    await signInAndLeaveLogin(page, email);

    return { context, page };
}

test.describe('membership applications', () => {
    test.use({ storageState: personas.moderator.storageState });

    test('a moderator denies an application with a reason the recruit then sees', async ({ page, browser }) => {
        await page.goto('/admin/troopers/approvals/index');

        const application = card(page, seeded.recruitToDeny.name);
        await application.getByRole('button', { name: 'Deny' }).click();
        await application.getByLabel('Denial Reason (optional)').fill('E2E: no matching TKID on the Legion roster.');
        await application.getByRole('button', { name: 'Confirm Denial' }).click();
        await expect(application.getByText('Successfully Submitted')).toBeVisible();

        await page.reload();
        await expect(card(page, seeded.recruitToDeny.name)).toHaveCount(0);

        const recruit = await asTrooper(browser, seeded.recruitToDeny.email);
        await expect(recruit.page).toHaveURL(/\/account\/denied$/);
        await expect(recruit.page.getByText('E2E: no matching TKID on the Legion roster.')).toBeVisible();
        await recruit.context.close();
    });

    test('a moderator approves a request to join a squad', async ({ page, browser }) => {
        await page.goto('/admin/troopers/approvals/index');

        const request = card(page, seeded.clubJoiner.name);
        await expect(request.getByText('E2E Squad')).toBeVisible();
        // The 501st roster lookup beside it can repeat the TKID, so check the request's own field.
        await expect(request.getByRole('definition').filter({ hasText: '93456' })).toBeVisible();

        await request.getByRole('button', { name: 'Approve' }).click();
        await expect(request.getByText('Successfully Submitted')).toBeVisible();

        const trooper = await asTrooper(browser, seeded.clubJoiner.email);
        await trooper.page.goto('/account');
        await trooper.page.locator('#app button.nav-link').filter({ hasText: 'Memberships' }).click();
        await expect(trooper.page.getByText('E2E Squad').filter({ visible: true }).first()).toBeVisible();
        await trooper.context.close();
    });

    test('a moderator denies a request to join a squad', async ({ page }) => {
        await page.goto('/admin/troopers/approvals/index');

        const request = card(page, seeded.clubRefused.name);
        await request.getByRole('button', { name: 'Deny' }).click();
        await request.getByLabel('Denial Reason (optional)').fill('E2E: not on the squad roster.');
        await request.getByRole('button', { name: 'Confirm Denial' }).click();
        await expect(request.getByText('Successfully Submitted')).toBeVisible();

        await page.reload();
        await expect(card(page, seeded.clubRefused.name)).toHaveCount(0);
    });
});

test.describe('permission requests', () => {
    async function requestPromotion(page, name, role) {
        await page.goto('/admin/troopers/permissions');

        await page.getByLabel('Trooper').click();
        const modal = page.getByRole('dialog');
        await modal.getByRole('textbox').fill(name);
        await modal.getByRole('button', { name }).click();
        await expect(modal).toBeHidden();

        await page.getByLabel('Role').selectOption({ label: role });
        await page.getByLabel('Note for the administrator').fill(`E2E: ${name} runs our sign-up desk.`);
        await page.getByRole('button', { name: 'Submit Request' }).click();
    }

    test.describe('a moderator asks', () => {
        test.use({ storageState: personas.moderator.storageState });

        test('for two troopers to become event coordinators', async ({ page }) => {
            await requestPromotion(page, seeded.promotionCandidate, 'Make event coordinator');
            await expect(page.getByText(seeded.promotionCandidate).last()).toBeVisible();

            await requestPromotion(page, seeded.promotionRefused, 'Make event coordinator');
            await expect(page.getByText(seeded.promotionRefused).last()).toBeVisible();
        });
    });

    test.describe('an administrator decides', () => {
        test.use({ storageState: personas.admin.storageState });

        test('approving one request and denying the other', async ({ page }) => {
            await page.goto('/admin/troopers/permissions/approvals');

            const approve = card(page, seeded.promotionCandidate);
            await expect(approve.getByText('runs our sign-up desk')).toBeVisible();
            await approve.getByRole('button', { name: 'Approve' }).click();
            await expect(approve).toHaveCount(0);

            const deny = card(page, seeded.promotionRefused);
            await deny.getByRole('button', { name: 'Deny' }).click();
            await deny.getByLabel('Denial Reason (optional)').fill('E2E: not this season.');
            await deny.getByRole('button', { name: 'Confirm Denial' }).click();
            await expect(deny).toHaveCount(0);

            await page.goto('/admin/troopers?search_term=E2E+Promotion');
            await expect(page.getByRole('row').filter({ hasText: seeded.promotionCandidate })).toContainText(/Event Coordinator/i);
            await expect(page.getByRole('row').filter({ hasText: seeded.promotionRefused })).not.toContainText(/Event Coordinator/i);
        });
    });
});
