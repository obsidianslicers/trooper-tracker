import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';

howTo('07-command-staff-overview', 'Command Staff - Overview', { as: troopers.jordan }, async ({ page, director: d }) => {
    await d.goto('/events');
    await d.action();

    d.caption('Command Staff opens the tools for running your garrison.');
    await d.click(page.locator('.navbar').getByRole('link', { name: /command staff/i }).first(), { pause: 2000 });

    d.caption('The dashboard shows what is waiting for you: applications, costumes and troops to check.');
    await d.scroll(350, { pause: 2000 });
    await d.scroll(400, { pause: 2000 });
    await d.scroll(-750, { pause: 800 });

    d.caption('Troopers: find anyone and edit their profile, clubs and role.');
    await d.click(page.locator('.card').filter({ hasText: /^\s*Troopers/ }).first(), { pause: 2200 });
    await d.goto('/admin');

    d.caption('Events: create, edit, staff and cancel troops.');
    await d.click(page.locator('.card').filter({ hasText: /^\s*Events/ }).first(), { pause: 2200 });
    await d.goto('/admin');

    d.caption('Reports & Stats: troops, charity raised and activity across your garrison.');
    await d.click(page.locator('.card').filter({ hasText: 'Reports & Stats' }).first(), { pause: 2500 });
    await d.scroll(400, { pause: 1500 });
});
