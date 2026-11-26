import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';

howTo('11-guests-and-friends', 'Bringing Guests & Friends', { as: troopers.sam }, async ({ page, director: d }) => {
    await d.goto('/events');
    await d.page.getByRole('link', { name: 'Riverside Children\'s Hospital Visit' }).first().click();
    await page.waitForLoadState('networkidle');
    const shift = page.locator('[id^="shift-container-"]').first();
    await shift.scrollIntoViewIfNeeded();
    await d.action();

    d.caption('Sign up for the shift first; then you can bring others.');
    await d.click(shift.locator('.shift-signup__button'));
    const form = shift.locator('.shift-signup .shift-signup-primary--form');
    await d.select(form.getByLabel('Costume', { exact: true }), { label: 'TK-Stormtrooper (ANH Stunt)' });
    await d.click(form.getByRole('button', { name: 'Confirm' }), { pause: 1800 });

    d.caption('A guest is someone without a tracker account, such as a handler or a photographer.');
    await d.click(shift.getByRole('button', { name: 'Add a guest' }));
    await d.type(shift.getByPlaceholder("Guest's name"), 'Jesse Rivera');
    await d.click(shift.locator('.shift-links .shift-signup-primary--form').getByRole('button', { name: 'Confirm' }), { pause: 2000 });

    d.caption('A friend is another trooper. Press Add a friend and pick them.');
    await d.click(shift.getByRole('button', { name: 'Add a friend' }));
    const picker = page.locator('.modal.show');
    await d.pause(1200);
    await d.click(picker.getByRole('button', { name: /Taylor Brooks/ }).first());

    d.caption('Choose their costume and Confirm. They are told you signed them up.');
    const friendForm = shift.locator('.friend-signup-step .shift-signup-primary--form');
    await d.hover(friendForm.getByLabel('Costume', { exact: true }), { pause: 1200 });
    await d.click(friendForm.getByRole('button', { name: 'Confirm' }), { pause: 2200 });
    await d.hover(shift.locator('.roster-row').filter({ hasText: 'Taylor Brooks' }).first(), { pause: 1500 });

    d.caption('Use Edit on their row to change or cancel their sign-up.');
    await d.hover(shift.locator('.roster-row[id^="roster-guest-"]').getByRole('button', { name: 'Edit' }), { pause: 2000 });

    d.caption('Troopers you sign up are listed under Account, Friends.');
    await d.goto('/account');
    await d.click(page.locator('#app button.nav-link').filter({ hasText: 'Friends' }).first(), { pause: 3000 });
});
