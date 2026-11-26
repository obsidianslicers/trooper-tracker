import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';

howTo('06-adding-an-organization', 'Adding An Organization', { as: troopers.sam }, async ({ page, director: d }) => {
    await d.goto('/account');
    await d.action();

    d.caption('Go to Account and open the Memberships tab.');
    await d.click(page.locator('#app button.nav-link').filter({ hasText: 'Memberships' }).first(), { pause: 1800 });

    d.caption('Choose the club you want to join.');
    await d.select(page.getByLabel('Organization to Join'), { label: 'Rebel Legion' });

    d.caption('Enter your ID for that club.');
    await d.type(page.getByLabel('Forum Username'), 'samrivera');

    d.caption('Press Request Access. It shows as pending until that club\'s Command Staff approve it.');
    await d.click(page.getByRole('button', { name: 'Request Access' }), { pause: 3000 });
    await d.hover(page.getByText(/pending/i).first(), { pause: 2000 });
});
