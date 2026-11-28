import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';

function tab(page, name) {
    return page.locator('#app button.nav-link').filter({ hasText: name }).first();
}

howTo('02-setting-up-your-profile', 'Setting Up Your Profile', { as: troopers.kai }, async ({ page, director: d }) => {
    await d.goto('/events');
    await d.action();

    d.caption('Open Account, then Profile.');
    await d.click(page.locator('.navbar').getByRole('button', { name: /account/i }).first());
    await d.click(page.locator('.navbar').getByRole('link', { name: 'Profile' }).first(), { pause: 1500 });
    await d.click(tab(page, 'Profile'));

    d.caption('Change the name shown on rosters, and your phone number.');
    await d.type(page.getByLabel('Display Name'), 'Kai "Bolt" Nakamura');
    await d.type(page.getByLabel('Phone (optional)'), '555-0188');

    d.caption('Pick a theme for the whole site.');
    await d.select(page.getByLabel('Theme'), { label: 'Rebel' });

    d.caption('Press Save.');
    await d.click(page.getByRole('button', { name: 'Save' }), { pause: 2500 });

    d.caption('The other tabs hold your costumes, club memberships and notifications.');
    for (const name of ['Costumes', 'Memberships', 'Notifications']) {
        await d.click(tab(page, name), { pause: 1500 });
    }
});
