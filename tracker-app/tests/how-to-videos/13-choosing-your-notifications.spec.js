import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';

howTo('13-choosing-your-notifications', 'Choosing Your Notifications', { as: troopers.sam }, async ({ page, director: d }) => {
    await d.goto('/account');
    await d.action();

    d.caption('Go to Account and open Notifications.');
    await d.click(page.locator('#app button.nav-link').filter({ hasText: 'Notifications' }).first(), { pause: 1200 });

    d.caption('Organization Event Notifications: which clubs and squads you hear about new events from.');
    await d.click(page.getByRole('button', { name: /Organization Event Notifications/ }), { pause: 1500 });

    d.caption('Notification Frequency: how often emails about new events arrive.');
    await d.select(page.getByLabel('Notification Frequency'), { label: 'Daily' }, { pause: 1500 });

    d.caption('Tick or untick a club. Changes save as you make them.');
    await d.click(page.getByLabel('Saber Guild'), { pause: 1500 });

    d.caption('Notification Preferences: for each kind of message, email, the mobile app, or the website.');
    await d.click(page.getByRole('button', { name: /Organization Event Notifications/ }), { pause: 600 });
    await d.click(page.getByRole('button', { name: /Notification Preferences/ }), { pause: 1800 });
    await d.scroll(300, { pause: 2500 });

    d.caption('Website notifications appear under the bell.');
    await d.scroll(-600, { pause: 500 });
    await d.click(page.locator('.navbar').getByRole('button', { name: 'Notifications', exact: true }), { pause: 3000 });
});
