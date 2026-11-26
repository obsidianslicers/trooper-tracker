import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';

howTo('16-roles-and-permissions', 'Command Staff - Roles & Permissions', { as: troopers.jordan }, async ({ page, director: d }) => {
    await d.goto('/service-records/command-staff');
    await d.action();

    d.caption('Service Records, Command Staff lists who runs each club, and their role.');
    await d.pause(2500);

    d.caption('To change someone\'s role, find them under Command Staff, Troopers.');
    await d.goto('/admin/troopers');
    await d.type(page.getByPlaceholder(/Search Name/), 'Drew Kim');
    await page.keyboard.press('Enter');
    await d.pause(1500);
    const row = page.getByRole('row').filter({ hasText: 'Drew Kim' });
    await d.click(row.getByRole('button').last(), { pause: 600 });
    await d.click(row.locator('a[href*="/admin/troopers/"]').last(), { pause: 1500 });

    d.caption('Open the Authority tab.');
    await d.click(page.locator('#app button.nav-link').filter({ hasText: 'Authority' }).first(), { pause: 1500 });

    d.caption('Event Coordinators manage events; Moderators also approve troopers and costumes for the clubs you tick.');
    await d.select(page.getByLabel('Role'), { label: 'Moderator' });
    await d.click(page.getByRole('row').filter({ hasText: 'Sunrise Garrison' }).getByRole('checkbox').first(), { pause: 1500 });

    d.caption('Staff Officers and System Owners run the whole tracker. Press Update Authority to save.');
    await d.click(page.getByRole('button', { name: 'Update Authority' }), { pause: 3000 });
});
