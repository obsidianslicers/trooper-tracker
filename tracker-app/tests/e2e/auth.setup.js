// Signs each persona in once and saves the session, so specs start already signed in.
import { test as setup } from '@playwright/test';
import { personas } from './support/personas.js';
import { signIn, expectSignedIn } from './support/auth.js';

for (const persona of Object.values(personas)) {
    setup(`sign in as ${persona.key}`, async ({ page }) => {
        await signIn(page, persona.email);
        await expectSignedIn(page);
        await page.context().storageState({ path: persona.storageState });
    });
}
