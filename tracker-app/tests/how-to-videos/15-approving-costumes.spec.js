import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';

howTo('15-approving-costumes', 'Command Staff - Approving Costumes', { as: troopers.alex }, async ({ page, director: d }) => {
    await d.goto('/admin');
    await d.action();

    d.caption('Costumes troopers add themselves wait for review. Open Costume Approvals.');
    await d.click(page.locator('.card').filter({ hasText: 'Costume Approvals' }).first(), { pause: 2000 });

    d.caption('Each row shows the trooper, the costume and the club.');
    const row = page.getByRole('row').filter({ hasText: 'Riley Patel' }).first();
    await d.hover(row, { pause: 2000 });

    d.caption('Approve it once you have checked it, or Deny it to take it off their profile.');
    await d.click(row.getByRole('button', { name: 'Approve' }), { pause: 2500 });

    d.caption('Approved costumes can be revoked later from the trooper\'s Costumes tab.');
    await d.goto(`/admin/troopers?search_term=${encodeURIComponent('Riley Patel')}`);
    const link = page.getByRole('row').filter({ hasText: 'Riley Patel' }).locator('a[href*="/admin/troopers/"]').last();
    await d.goto(`${await link.getAttribute('href')}?tab=costumes`);
    await d.hover(page.getByRole('button', { name: 'Revoke' }).first(), { pause: 3000 });
});
