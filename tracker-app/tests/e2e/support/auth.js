import { expect } from '@playwright/test';
import { PASSWORD } from './personas.js';

export async function signIn(page, email, password = PASSWORD) {
    await page.goto('/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: 'Login' }).click();
}

export async function expectSignedIn(page) {
    await expect(page).toHaveURL(/\/events/);
}
