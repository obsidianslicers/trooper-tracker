// The event page's "How sign-up works" tour, started from its button. The E2E run turns
// starting by itself off (TRACKER_SIGNUP_TOUR=off in support/env.js) so it never covers other
// tests; who it starts for is covered by SignUpTourTest and tests/js/svelte/events/vms/SignUpTourViewModel.test.ts.
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';

test.use({ storageState: personas.trooper.storageState });

async function openSignupEvent(page) {
    await page.goto('/events');
    await page.getByRole('link', { name: seeded.signupEvent }).first().click();
    await expect(page.getByRole('heading', { name: seeded.signupEvent })).toBeVisible();
}

test.describe('the sign-up tour', () => {
    test('the button walks through signing up on example rows, then closes', async ({ page }) => {
        await openSignupEvent(page);
        const card = page.getByRole('dialog', { name: 'How signing up works' });
        await expect(card).toBeHidden();

        await page.getByRole('button', { name: 'How sign-up works' }).click();

        await expect(card).toBeVisible();
        await expect(page.getByText('Example only, not a real sign-up')).toBeVisible();
        const counter = await card.getByText(/^1 of \d+$/).textContent();
        const steps = Number(counter.split(' of ')[1]);
        expect(steps).toBeGreaterThan(3);

        const dialog = page.getByRole('dialog');
        for (let step = 1; step < steps; step++) {
            await dialog.getByRole('button', { name: 'Next' }).click();
            await expect(dialog.getByText(`${step + 1} of ${steps}`)).toBeVisible();
        }
        await expect(dialog.getByRole('heading', { name: "That's it" })).toBeVisible();
        await dialog.getByRole('button', { name: 'Done' }).click();

        await expect(dialog).toBeHidden();
        await expect(page.getByText('Example only, not a real sign-up')).toBeHidden();
    });

    test('Escape closes it, and the button brings it back', async ({ page }) => {
        await openSignupEvent(page);
        const replay = page.getByRole('button', { name: 'How sign-up works' });

        await replay.click();
        await expect(page.getByRole('dialog')).toBeVisible();
        await page.keyboard.press('Escape');
        await expect(page.getByRole('dialog')).toBeHidden();

        await replay.click();
        await expect(page.getByRole('dialog', { name: 'How signing up works' })).toBeVisible();
    });
});
