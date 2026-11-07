// Every page a role can reach opens without a server or script error, and pages above a
// role's rank are refused. Flows on these pages get their own specs.
import { test } from '@playwright/test';
import { personas } from './support/personas.js';
import { expectPageLoads, expectForbidden } from './support/pages.js';

const MEMBER_PAGES = [
    '/',
    '/faq',
    '/events',
    '/events/calendar',
    '/events/closed',
    '/events/cancelled',
    '/account',
    '/account/notices',
    '/service-records/leaderboard',
    '/service-records/awards',
    '/service-records/achievements',
    '/service-records/command-staff',
    '/service-records/photo-gallery',
];

const MODERATOR_PAGES = [
    '/admin',
    '/admin/troopers',
    '/admin/troopers/approvals/index',
    '/admin/troopers/permissions',
    '/admin/troopers/recruit',
    '/admin/events',
    '/admin/events/create',
    '/admin/awards',
    '/admin/awards/create',
    '/admin/notices',
    '/admin/notices/create',
    '/admin/organizations',
    '/admin/reports',
    '/admin/service-records/missing-credits',
];

const ADMIN_ONLY_PAGES = [
    '/admin/system-check',
    '/admin/troopers/merge',
    '/admin/troopers/permissions/approvals',
    '/admin/costumes',
    '/admin/costumes/create',
    '/admin/costumes/merge',
    '/admin/faq/index',
    '/admin/plugins',
];

test.describe('as a member', () => {
    test.use({ storageState: personas.member.storageState });

    for (const url of MEMBER_PAGES) {
        test(`opens ${url}`, async ({ page }) => {
            await expectPageLoads(page, url);
        });
    }

    test('is refused Command Staff pages', async ({ page }) => {
        for (const url of ['/admin', '/admin/troopers', '/admin/costumes']) {
            await expectForbidden(page, url);
        }
    });
});

test.describe('as a moderator', () => {
    test.use({ storageState: personas.moderator.storageState });

    for (const url of MODERATOR_PAGES) {
        test(`opens ${url}`, async ({ page }) => {
            await expectPageLoads(page, url);
        });
    }

    test('is refused administrator-only pages', async ({ page }) => {
        for (const url of ADMIN_ONLY_PAGES) {
            await expectForbidden(page, url);
        }
    });
});

test.describe('as an administrator', () => {
    test.use({ storageState: personas.admin.storageState });

    for (const url of [...MODERATOR_PAGES, ...ADMIN_ONLY_PAGES]) {
        test(`opens ${url}`, async ({ page }) => {
            await expectPageLoads(page, url);
        });
    }
});
