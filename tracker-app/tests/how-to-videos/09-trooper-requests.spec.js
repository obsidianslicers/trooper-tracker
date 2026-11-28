import { howTo, fakeRosterLookup } from './support/director.js';
import { troopers } from './support/cast.js';

function card(page, name) {
    return page.locator('.card').filter({ hasText: name });
}

howTo('09-trooper-requests', 'Command Staff - Trooper Requests', { as: troopers.alex }, async ({ page, director: d }) => {
    await fakeRosterLookup(page, { 91234: 'Chris Walker', 80056: 'Avery Scott' });
    await d.goto('/admin');
    await d.action();

    d.caption('New applications and club requests wait under Command Staff for your clubs.');
    await d.pause(1500);
    await d.goto('/admin/troopers/approvals');

    d.caption('Each card shows who is asking, the club and their ID, checked against the club\'s roster.');
    await d.hover(card(page, 'Chris Walker').first(), { pause: 2500 });

    d.caption('Approve lets them in; they get an email.');
    await d.click(card(page, 'Chris Walker').first().getByRole('button', { name: 'Approve' }), { pause: 2200 });

    d.caption('A trooper asking to join a squad shows here too.');
    await d.hover(card(page, 'Avery Scott').first(), { pause: 2000 });

    d.caption('Deny asks for an optional reason, which the trooper sees.');
    await d.click(card(page, 'Avery Scott').first().getByRole('button', { name: 'Deny' }));
    await d.pause(1500);
    await d.click(card(page, 'Avery Scott').first().getByRole('button', { name: 'Cancel' }).or(
        card(page, 'Avery Scott').first().getByRole('button', { name: 'Never mind' })).first());
    await d.click(card(page, 'Avery Scott').first().getByRole('button', { name: 'Approve' }), { pause: 2500 });
});
