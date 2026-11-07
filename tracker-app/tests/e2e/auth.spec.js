import { test, expect } from '@playwright/test';
import { personas } from './support/personas.js';
import { signIn, expectSignedIn } from './support/auth.js';

test.describe('signing in', () => {
    test('a guest is sent to the login page', async ({ page }) => {
        await page.goto('/events');

        await expect(page).toHaveURL(/\/login/);
        await expect(page.getByRole('button', { name: 'Login' })).toBeVisible();
    });

    test('a wrong password is refused', async ({ page }) => {
        await signIn(page, personas.member.email, 'not-the-password');

        await expect(page.getByText('Invalid email or password.')).toBeVisible();
        await expect(page).toHaveURL(/\/login/);
    });

    test('a member signs in and lands on events', async ({ page }) => {
        await signIn(page, personas.member.email);

        await expectSignedIn(page);
    });

    test('a signed-in trooper can sign out', async ({ page }) => {
        await signIn(page, personas.member.email);
        await expectSignedIn(page);

        await page.goto('/logout');
        await page.goto('/events');

        await expect(page).toHaveURL(/\/login/);
    });
});
