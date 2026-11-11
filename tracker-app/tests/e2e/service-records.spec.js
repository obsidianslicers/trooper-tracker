// Service records and reports: a trooper's record and its tabs, the leaderboard, awards,
// achievements, Command Staff and costume pages; a moderator assigning missing credit; and
// the reports that run on the browser tests' SQLite database.
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';

async function openRecord(page, name) {
    await page.goto('/service-records/leaderboard');
    await page.getByRole('link', { name }).first().click();
    await expect(page).toHaveURL(/\/service-records\/trooper\/\d+$/);
}

function clubTroops(page, club) {
    return page.getByRole('row').filter({ hasText: club }).first();
}

test.describe('as a member', () => {
    test.use({ storageState: personas.member.storageState });

    test('a trooper\'s service record shows their troops, awards and costumes', async ({ page }) => {
        await openRecord(page, seeded.veteran);

        await expect(clubTroops(page, '501st Legion')).toContainText('1 troops');
        await expect(page.getByRole('link', { name: 'Edit' })).toHaveCount(0);

        await page.getByRole('tab', { name: 'History' }).click();
        await expect(page.locator('#recent-shifts').getByText(seeded.charityWalk).filter({ visible: true }).first()).toBeVisible();

        await page.getByRole('tab', { name: 'Awards' }).click();
        await expect(page.locator('#awards')).toContainText(seeded.veteranAward);

        await page.getByRole('tab', { name: 'Costumes' }).click();
        await expect(page.locator('#costumes')).toContainText(seeded.veteranCostume);
    });

    test('the leaderboard ranks troopers and links to the costumes they troop in', async ({ page }) => {
        await page.goto('/service-records/leaderboard');
        await expect(page.getByRole('link', { name: seeded.veteran })).toBeVisible();

        // Clicking a top costume loads its rankings into the panel beside it.
        await page.getByText(seeded.veteranCostume, { exact: true }).first().click();
        await expect(page.locator('#costume-stats-panel')).toContainText(seeded.veteran);
    });

    test('awards and achievements from the last 30 days', async ({ page }) => {
        await page.goto('/service-records/awards');
        await expect(page.getByRole('row').filter({ hasText: seeded.veteranAward })).toContainText(seeded.veteran);

        await page.goto('/service-records/achievements');
        await expect(page.getByRole('row').filter({ hasText: seeded.veteran })).toContainText('First Troop');
    });

    test('the Command Staff page lists who moderates which club', async ({ page }) => {
        await page.goto('/service-records/command-staff');
        await expect(page.getByRole('row').filter({ hasText: personas.moderator.name })).toContainText('501st Legion');
        await expect(page.getByRole('row').filter({ hasText: personas.admin.name })).toContainText('System Owner');
    });
});

test.describe('as a moderator', () => {
    test.use({ storageState: personas.moderator.storageState });

    test('sees an Edit link on a service record', async ({ page }) => {
        await openRecord(page, seeded.veteran);
        await expect(page.getByRole('link', { name: 'Edit' })).toHaveAttribute('href', /\/admin\/troopers\/\d+$/);
    });

    test('assigns a club the credit for a troop that counted for none', async ({ page }) => {
        await page.goto('/admin/service-records/missing-credits');

        const row = page.getByRole('row').filter({ hasText: seeded.uncreditedTroop });
        await expect(row).toContainText(seeded.uncredited);
        await row.getByLabel('501st Legion').check();
        await row.getByRole('button', { name: 'Assign Credit' }).click();
        await expect(row).toHaveCount(0);

        await page.goto('/admin/service-records/missing-credits');
        await expect(page.getByText('No missing-credit shifts found.')).toBeVisible();

        await openRecord(page, seeded.uncredited);
        await expect(clubTroops(page, '501st Legion')).toContainText('1 troops');
    });

    test('the reports dashboard and its reports', async ({ page }) => {
        await page.goto('/admin/reports');
        await expect(page.getByText('Unit Performance Leaderboard', { exact: false })).toBeVisible();
        await page.getByRole('link', { name: '360D' }).click();
        await expect(page).toHaveURL(/days=360/);

        await page.goto('/admin/reports/event-summary');
        await expect(page.getByRole('row').filter({ hasText: seeded.charityWalk })).toBeVisible();

        await page.goto('/admin/reports/event-type-count');
        await expect(page.getByRole('row').filter({ hasText: 'Regular' })).toBeVisible();
        await expect(page.getByRole('row').filter({ hasText: seeded.uncountedType.name })).toBeVisible();

        await page.goto('/admin/reports/troopers-without-activity');
        await expect(page.getByRole('row').filter({ hasText: seeded.costumeOwner })).toBeVisible();
        await expect(page.getByRole('row').filter({ hasText: seeded.veteran })).toHaveCount(0);

        await page.goto('/admin/reports/status-change-log');
        await expect(page.getByText('Troopers who ATTENDED an event', { exact: false })).toBeVisible();
    });
});
