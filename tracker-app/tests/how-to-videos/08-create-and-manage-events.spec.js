import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';

function field(page, label) {
    return page.getByLabel(label, { exact: true });
}

function tab(page, name) {
    return page.locator('#app button.nav-link').filter({ hasText: name }).first();
}

howTo('08-create-and-manage-events', 'Command Staff - Create & Manage Events', { as: troopers.casey }, async ({ page, director: d }) => {
    await d.goto('/admin/events');
    await d.action();

    d.caption('Under Command Staff, Events lists every troop. Press Create to add one.');
    await d.pause(1500);
    await d.click(page.locator('a.btn[href*="/admin/events/create"]').first(), { pause: 1500 });

    d.caption('Pick the hosting club.');
    await d.click(field(page, 'Hosting Organization'));
    const picker = page.getByRole('dialog');
    await d.type(picker.getByRole('textbox'), '501st');
    await d.click(picker.getByRole('button', { name: '501st Legion', exact: true }));

    d.caption('Paste the club\'s event request to fill the form in, or enter it by hand.');
    await d.hover(page.getByLabel('501st Legion Message'), { pause: 1500 });
    await d.click(page.getByText('Input Manually'));

    d.caption('Give it a name, a start and an end.');
    await d.type(field(page, 'Name'), 'Sunrise Mall Grand Reopening');
    await d.type(field(page, 'Starts'), '2027-01-16 10:00AM');
    await d.type(field(page, 'Ends'), '2027-01-16 2:00PM');

    d.caption('Fill in the venue and anything else troopers need.');
    await d.click(page.getByRole('button', { name: /^Location/ }));
    await d.type(field(page, 'Venue'), 'Sunrise Mall');

    d.caption('Press Create.');
    await d.click(page.getByRole('button', { name: 'Create', exact: true }), { pause: 2500 });

    d.caption('Each tab saves as you go: shifts, roster, charity and more.');
    await d.click(tab(page, 'Shifts'), { pause: 2000 });
    await d.click(tab(page, 'Roster'), { pause: 2000 });
    await d.click(tab(page, 'Charity'), { pause: 2000 });

    d.caption('View Event shows the page troopers see.');
    await d.click(page.getByRole('link', { name: 'View Event' }).first(), { pause: 3000 });
});
