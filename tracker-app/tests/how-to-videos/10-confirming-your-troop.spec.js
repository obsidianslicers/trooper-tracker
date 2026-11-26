import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';

howTo('10-confirming-your-troop', 'Confirming Your Troop', { as: troopers.sam }, async ({ page, director: d }) => {
    await d.goto('/events');
    await d.action();

    d.caption('After a troop, the bell asks whether you made it.');
    const bell = page.locator('.navbar').getByRole('button', { name: 'Notifications', exact: true });
    await d.click(bell, { pause: 2200 });

    d.caption('Press Made It to count the troop on your service record, or Missed It if you could not go.');
    const menu = page.locator('.notifications-menu');
    await d.hover(menu.getByText('Springfield Library Grand Opening').first(), { pause: 1500 });
    await d.click(menu.getByRole('button', { name: 'Made It' }).first(), { pause: 2500 });

    d.caption('You can also answer on the event page, or from the email the tracker sends.');
    await d.click(page.locator('.navbar').getByRole('button', { name: /service records/i }).first());
    await d.click(page.locator('.navbar').getByRole('link', { name: 'My Service' }).first(), { pause: 1500 });

    d.caption('The troop now shows in your history.');
    await d.click(page.getByRole('tab', { name: 'History' }), { pause: 3000 });
});
