import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';

howTo('05-reading-your-service-record', 'Reading Your Service Record', { as: troopers.sam }, async ({ page, director: d }) => {
    await d.goto('/events');
    await d.action();

    d.caption('Service Records, then My Service, opens your record.');
    await d.click(page.locator('.navbar').getByRole('button', { name: /service records/i }).first());
    await d.click(page.locator('.navbar').getByRole('link', { name: 'My Service' }).first(), { pause: 1500 });

    d.caption('The summary shows your troops, hours, charity raised and troops for each club.');
    await d.hover(page.getByText('Service Summary', { exact: false }).first(), { pause: 2500 });

    d.caption('Upcoming lists the shifts you are signed up for; History lists past troops.');
    for (const name of ['History', 'Awards', 'Costumes', 'Photos']) {
        if (name === 'Awards') {
            d.caption('Awards, costumes and tagged photos each have a tab.');
        }
        await d.click(page.getByRole('tab', { name }), { pause: 1800 });
    }

    d.caption('Achievements are earned automatically as you troop.');
    await d.scroll(400, { pause: 2000 });
    await d.scroll(-400, { pause: 500 });

    d.caption('The Leaderboard ranks everyone; click a name to open their record.');
    await d.click(page.locator('.navbar').getByRole('button', { name: /service records/i }).first());
    await d.click(page.locator('.navbar').getByRole('link', { name: 'Leaderboard' }).first(), { pause: 2500 });
});
