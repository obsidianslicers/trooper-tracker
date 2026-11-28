import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';

howTo('04-browsing-and-joining-events', 'Browsing & Joining Events', { as: troopers.sam }, async ({ page, director: d }) => {
    await d.goto('/events');
    await d.action();

    d.caption('Upcoming Events lists every troop you can sign up for.');
    await d.pause(1800);

    d.caption('Search by event name or venue.');
    await d.type(page.getByRole('searchbox', { name: 'Search events' }).filter({ visible: true }), 'parade');
    await d.pause(1200);
    await page.getByRole('searchbox', { name: 'Search events' }).filter({ visible: true }).fill('');
    await d.pause(600);

    d.caption('Switch between the list, calendar, map and past troops.');
    const views = page.getByRole('button', { name: 'Event views' }).filter({ visible: true });
    await d.click(views);
    await d.click(page.getByRole('link', { name: 'Calendar' }).filter({ visible: true }), { pause: 2200 });
    await d.click(views);
    await d.click(page.getByRole('link', { name: 'List' }).filter({ visible: true }), { pause: 1200 });

    d.caption('Open an event to see its details, shifts and who is going.');
    await d.click(page.getByRole('link', { name: 'Downtown Holiday Parade' }).first(), { pause: 1800 });
    await d.scroll(350);

    d.caption('Press Sign Up on a shift and pick your costume.');
    const shift = page.locator('[id^="shift-container-"]').first();
    await d.click(shift.locator('.shift-signup__button'));
    const form = shift.locator('.shift-signup .shift-signup-primary--form');
    await d.select(form.getByLabel('Costume', { exact: true }), { label: 'TK-Stormtrooper (ANH Stunt)' });

    d.caption('Confirm, and you are on the roster.');
    await d.click(form.getByRole('button', { name: 'Confirm' }), { pause: 2200 });
    await d.hover(shift.locator('.roster-row--mine'), { pause: 1800 });

    d.caption('Watch Event sends you updates; the calendar button adds it to your calendar.');
    await d.scroll(-2000, { pause: 600 });
    await d.click(page.getByRole('button', { name: 'Watch Event' }), { pause: 2000 });
});
