import { howTo } from './support/director.js';
import { troopers } from './support/cast.js';

howTo('03-adding-your-costumes', 'Adding Your Costumes', { as: troopers.taylor }, async ({ page, director: d }) => {
    await d.goto('/account');
    await d.action();

    d.caption('Go to Account and open the Costumes tab.');
    await d.click(page.locator('#app button.nav-link').filter({ hasText: 'Costumes' }).first(), { pause: 1500 });

    d.caption('Search for your costume.');
    await d.type(page.getByLabel('Find Your Costume'), 'Shore');
    await d.click(page.getByRole('button', { name: 'TS-Shoretrooper' }));

    d.caption('Tick the club it is approved with.');
    await d.click(page.getByRole('checkbox', { name: '501st Legion' }));

    d.caption('Press Add to Armory.');
    await d.click(page.getByRole('button', { name: 'Add to Armory' }), { pause: 2000 });

    d.caption('A costume you add waits for Command Staff to approve it before troops in it count.');
    await d.hover(page.getByRole('row').filter({ hasText: 'TS-Shoretrooper' }), { pause: 3000 });
});
