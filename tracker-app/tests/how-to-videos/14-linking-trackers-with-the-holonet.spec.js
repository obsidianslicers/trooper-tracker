import { howTo, PASSWORD } from './support/director.js';
import { troopers } from './support/cast.js';

async function myService(page, d) {
    await d.click(page.locator('.navbar').getByRole('button', { name: /service records/i }).first());
    await d.click(page.locator('.navbar').getByRole('link', { name: 'My Service' }).first(), { pause: 1500 });
}

howTo('14-linking-trackers-with-the-holonet', 'Linking Trackers with the HoloNet', { as: troopers.sam }, async ({ page, director: d }) => {
    await d.goto('/events');
    await myService(page, d);
    await d.action();

    d.caption('Troop with another garrison too? The HoloNet shows those troops on your service record.');
    await d.pause(2000);
    await d.click(page.getByRole('link', { name: /HoloNet/ }).first(), { pause: 1800 });

    d.caption('Pick the other garrison\'s tracker and press Link.');
    await d.select(page.getByLabel('Tracker'), { label: 'Mountain Garrison Tracker' });
    await d.click(page.getByRole('button', { name: 'Link', exact: true }), { pause: 1800 });

    d.caption('Sign in to that tracker with your account there.');
    await d.type(page.getByLabel('Email'), troopers.sam);
    await d.type(page.getByLabel('Password'), PASSWORD);
    await d.click(page.getByRole('button', { name: 'Login' }), { pause: 1800 });

    d.caption('It lists what it will share: your troops, never your email, phone or legal name. Press Allow.');
    await d.scroll(250, { pause: 2500 });
    await d.click(page.getByRole('button', { name: /Allow/ }).first(), { pause: 2500 });

    d.caption('Back home, the link is listed. Refresh or Unlink it at any time.');
    await d.pause(2500);

    d.caption('Your service record now counts those troops in your all-time totals.');
    await myService(page, d);
    await d.hover(page.getByText('All-Time Troops').first(), { pause: 2000 });

    d.caption('They are listed under History, and never count toward this garrison\'s awards or reports.');
    await d.click(page.getByRole('tab', { name: 'History' }), { pause: 1200 });
    await d.scroll(700, { pause: 1500 });
    await d.moveTo(page.getByText('Troops from the HoloNet').first());
    await d.pause(3500);
});
