// Joining the tracker: signing up, finishing setup, confirming an email, and the screens a
// trooper sees while their application is pending, after it is denied, or when visitor
// access runs out.
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';
import { signInAndLeaveLogin as signIn } from './support/auth.js';
import { verificationUrl } from './support/artisan.js';

const RECRUIT = {
    email: 'new-recruit@e2e.test',
    password: 'a-new-password',
    legalName: 'E2E Signup Legal',
    displayName: 'E2E Signup Recruit',
};

test.describe('signing up', () => {
    test('the sign-up page offers email sign-up and links to the form', async ({ page }) => {
        await page.goto('/signup');

        await page.getByRole('link', { name: 'Sign Up with Email' }).click();

        await expect(page).toHaveURL(/\/register$/);
        await expect(page.getByRole('button', { name: 'Register' })).toBeVisible();
    });

    test('the form cannot be opened without starting sign-up first', async ({ page }) => {
        await page.goto('/register');

        await expect(page).toHaveURL(/\/signup$/);
    });

    test('a recruit must pick a club', async ({ page }) => {
        await page.goto('/signup-email');
        await fillIdentity(page, { ...RECRUIT, email: 'no-club@e2e.test' });
        await page.getByLabel('Membership Role').selectOption({ label: 'Member' });

        await page.getByRole('button', { name: 'Register' }).click();

        await expect(page.locator('form').getByText('Please select at least one organization.')).toBeVisible();
        await expect(page).toHaveURL(/\/register$/);
    });

    test('a recruit registers, confirms their email and waits for approval', async ({ page, browser }) => {
        await page.goto('/signup-email');
        await fillIdentity(page, RECRUIT);
        await page.getByLabel('Membership Role').selectOption({ label: 'Member' });

        await page.getByLabel('501st Legion').check();
        await page.getByLabel('TKID').fill('91234');
        await page.getByLabel('Region/Garrison').selectOption({ label: 'E2E Garrison' });
        await page.getByLabel('Unit/Squad').selectOption({ label: 'E2E Squad' });

        await page.getByRole('button', { name: 'Register' }).click();

        await expect(page).toHaveURL(/\/thank-you$/);
        await expect(page.getByText('Thank You for Registering!')).toBeVisible();

        // A new account confirms its email, then waits for Command Staff.
        await signIn(page, RECRUIT.email, RECRUIT.password);
        await expect(page).toHaveURL(/\/verification\/notice$/);

        await page.goto(verificationUrl(RECRUIT.email));

        await expect(page).toHaveURL(/\/account\/pending$/);
        await expect(page.getByText('Your application is under review.')).toBeVisible();

        // Still pending: the rest of the tracker stays closed.
        await page.goto('/account');
        await expect(page).toHaveURL(/\/account\/pending$/);

        // The squad's moderator now has them in the approval queue.
        const moderator = await browser.newContext({ storageState: personas.moderator.storageState });
        const queue = await moderator.newPage();
        await queue.goto('/admin/troopers/approvals/index');
        await expect(queue.locator('.card').filter({ hasText: RECRUIT.displayName })).toBeVisible();
        await moderator.close();
    });
});

test.describe('account setup', () => {
    test('a trooper who has not finished setup is sent to it, and then gets in', async ({ page }) => {
        await signIn(page, seeded.needsSetup.email);

        await expect(page).toHaveURL(/\/account\/setup$/);

        await page.goto('/events');
        await expect(page).toHaveURL(/\/account\/setup$/);

        await legalName(page).fill('');
        await page.getByRole('button', { name: 'Update' }).click();
        await expect(page.getByText('The legal name field is required.').first()).toBeVisible();

        await legalName(page).fill('E2E Setup Legal Name');
        await page.getByRole('button', { name: 'Update' }).click();

        await expect(page).toHaveURL(/\/account$/);

        await page.goto('/events');
        await expect(page).toHaveURL(/\/events$/);
    });
});

test.describe('email verification', () => {
    test('an unconfirmed trooper is asked to confirm, can resend, and the link confirms', async ({ page }) => {
        await signIn(page, seeded.unverified.email);

        await page.goto('/events');
        await expect(page).toHaveURL(/\/verification\/notice$/);
        await expect(page.getByText('Check Your Inbox')).toBeVisible();

        await page.getByRole('button', { name: 'Resend Verification Email' }).click();
        await expect(page.getByText('A new verification link has been sent to your email.')).toBeVisible();

        await page.goto(verificationUrl(seeded.unverified.email));

        await expect(page).toHaveURL(/\/account$/);
        await expect(page.getByText('Thank you for verifying your email!')).toBeVisible();

        await page.goto('/events');
        await expect(page).toHaveURL(/\/events$/);
    });

    test('a tampered link is refused', async ({ page }) => {
        await signIn(page, seeded.unverified.email);

        await page.goto('/verification/email/1/not-a-hash?signature=nope');

        await expect(page).toHaveURL(/\/verification\/notice$/);
        await expect(page.getByText('The verification link has expired or is invalid.')).toBeVisible();
    });
});

test.describe('a denied application', () => {
    test('the trooper sees why, and can resubmit with a club', async ({ page }) => {
        await signIn(page, seeded.denied.email);

        await page.goto('/account');
        await expect(page).toHaveURL(/\/account\/denied$/);
        await expect(page.getByText('Your registration was not approved.')).toBeVisible();

        const tkid = page.locator('input[name="organizations[1][identifier]"]');
        await expect(tkid).toBeHidden();

        await page.getByLabel('501st Legion').check();
        await tkid.fill('92345');
        await page.locator('select[name="organizations[1][region_id]"]').selectOption({ label: 'E2E Garrison' });
        await page.locator('select[name="organizations[1][unit_id]"]').selectOption({ label: 'E2E Squad' });

        await page.getByRole('button', { name: 'Resubmit Application' }).click();

        await expect(page).toHaveURL(/\/account\/pending$/);
        await expect(page.getByText('Your application has been resubmitted.')).toBeVisible();
    });
});

test.describe('visitor renewal', () => {
    test('a visitor whose access ran out is sent to renew, and the request goes for review', async ({ page }) => {
        await signIn(page, seeded.expiredVisitor.email);

        await page.goto('/events');
        await expect(page).toHaveURL(/\/account\/visitor\/renew$/);
        await expect(page.getByText('Your visitor access has expired')).toBeVisible();

        await page.getByRole('button', { name: 'Request Renewal' }).click();

        await expect(page).toHaveURL(/\/thank-you$/);

        await page.goto('/account');
        await expect(page).toHaveURL(/\/account\/pending$/);
    });
});

test.describe('pages for signed-out visitors', () => {
    test('the inactive account page opens', async ({ page }) => {
        await page.goto('/inactive');

        await expect(page.getByText('Your account is currently inactive.')).toBeVisible();
    });
});

async function fillIdentity(page, recruit) {
    await page.getByLabel('Email').fill(recruit.email);
    await page.getByLabel('Password').fill(recruit.password);
    await page.getByLabel('Legal Name').fill(recruit.legalName);
    await page.getByLabel('Display Name').fill(recruit.displayName);
}

// The setup form's labels are not tied to their inputs.
function legalName(page) {
    return page.locator('input[name="legal_name"]');
}
