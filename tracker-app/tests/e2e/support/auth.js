import { expect } from '@playwright/test';
import { PASSWORD } from './personas.js';

export async function signIn(page, email, password = PASSWORD) {
    await page.goto('/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: 'Login' }).click();
}

// Signs in and waits until the app has sent the trooper on, so the next page.goto() does
// not race the redirect.
export async function signInAndLeaveLogin(page, email, password = PASSWORD) {
    await signIn(page, email, password);
    await page.waitForURL((url) => url.pathname !== '/login');
    await page.waitForLoadState('networkidle');
}

export async function expectSignedIn(page) {
    await expect(page).toHaveURL(/\/events/);
}
