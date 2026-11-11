// Admin-managed event types: the Event Types page, how the event form offers them, and the
// notice members see on events whose type doesn't count toward troop totals.
import { test, expect } from '@playwright/test';
import { personas, seeded } from './support/personas.js';

const FORM_NOTE = 'Non-troop-credit event: Does not count toward troop totals.';

function typeRow(page, name) {
    return page.getByRole('row').filter({ has: page.getByRole('link', { name, exact: true }) });
}

async function openCreateEventForm(page) {
    await page.goto(`/admin/events/create?organization_id=${seeded.legionId}`);
    await page.getByText('Input Manually').click();

    return page.getByLabel('Event Type');
}

// SortableJS listens for a real drag, so bring the rows on screen and move the mouse in
// steps rather than jumping.
async function dragRowAbove(page, name, aboveName) {
    await typeRow(page, name).scrollIntoViewIfNeeded();
    const handle = await typeRow(page, name).locator('.move-handle').boundingBox();
    const target = await typeRow(page, aboveName).boundingBox();

    await page.mouse.move(handle.x + handle.width / 2, handle.y + handle.height / 2);
    await page.mouse.down();
    await page.mouse.move(target.x + 20, target.y + 2, { steps: 15 });
    await page.mouse.up();
}

test.describe('as an administrator', () => {
    test.use({ storageState: personas.admin.storageState });

    test('creates a type that does not count toward troop totals', async ({ page }) => {
        await page.goto('/admin/event-types');
        await page.getByRole('link', { name: /event type$/i }).click();

        await page.getByLabel('Name').fill('E2E Meetup');
        await page.getByLabel('Description').fill('E2E social night, no costumes.');
        await page.getByLabel('Counts toward troop totals').uncheck();
        await page.getByRole('button', { name: 'Create' }).click();

        await expect(page).toHaveURL(/\/admin\/event-types$/);
        const row = typeRow(page, 'E2E Meetup');
        await expect(row).toContainText('E2E social night, no costumes.');
        await expect(row).toContainText('Non-troop-credit');
    });

    test('drags a type to change the order of the event form list', async ({ page }) => {
        await page.goto('/admin/event-types');

        await dragRowAbove(page, seeded.sortSecondType, seeded.sortFirstType);
        await expect(page.getByText('Event types reordered.').first()).toBeVisible();

        await page.reload();
        const rows = page.getByRole('row').filter({ hasText: /E2E Sort/ });
        await expect(rows).toHaveText([new RegExp(seeded.sortSecondType), new RegExp(seeded.sortFirstType)]);

        const options = (await openCreateEventForm(page)).locator('option').filter({ hasText: /E2E Sort/ });
        await expect(options).toHaveText([seeded.sortSecondType, seeded.sortFirstType]);
    });

    test('the event form preselects the first type and explains the chosen one', async ({ page }) => {
        const type = await openCreateEventForm(page);

        // Options are keyed by id, so check the label of the preselected one.
        await expect(type.locator('option:checked')).toHaveText('Regular');

        await type.selectOption({ label: seeded.uncountedType.name });
        await expect(page.getByText(seeded.uncountedType.description)).toBeVisible();
        await expect(page.getByText(FORM_NOTE)).toBeVisible();

        await type.selectOption({ label: 'Charity' });
        await expect(page.getByText(FORM_NOTE)).toBeHidden();
    });

    test('hides a type from the event form', async ({ page }) => {
        await page.goto('/admin/event-types');
        await typeRow(page, seeded.retiringType).getByRole('link', { name: seeded.retiringType }).click();

        await page.getByLabel('Offered on the event form').uncheck();
        await page.getByRole('button', { name: 'Update' }).click();

        await expect(page).toHaveURL(/\/admin\/event-types$/);
        await expect(typeRow(page, seeded.retiringType)).toContainText('Hidden');

        const type = await openCreateEventForm(page);
        await expect(type.locator('option', { hasText: seeded.retiringType })).toHaveCount(0);
    });

    test('deletes an unused type but not one that events use', async ({ page }) => {
        await page.goto('/admin/event-types');

        const inUse = typeRow(page, seeded.uncountedType.name);
        await inUse.getByRole('button', { name: 'Actions' }).click();
        await expect(inUse.getByRole('button', { name: 'Delete' })).toHaveCount(0);
        await page.keyboard.press('Escape');

        const unused = typeRow(page, seeded.unusedType);
        await unused.getByRole('button', { name: 'Actions' }).click();
        await unused.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText(`Are you sure you want to delete "${seeded.unusedType}"?`)).toBeVisible();
        await page.getByRole('button', { name: 'Delete', exact: true }).click();

        await expect(typeRow(page, seeded.unusedType)).toHaveCount(0);
        await page.reload();
        await expect(typeRow(page, seeded.unusedType)).toHaveCount(0);
    });

    test('warns that changing whether a type counts also changes past events', async ({ page }) => {
        await page.goto('/admin/event-types');
        await typeRow(page, seeded.uncountedType.name)
            .getByRole('link', { name: seeded.uncountedType.name })
            .click();

        const warning = page.getByText('This also applies to the 2 event(s) already using this type');
        await expect(warning).toBeHidden();

        await page.getByLabel('Counts toward troop totals').check();
        await expect(warning).toBeVisible();
    });
});

test.describe('as a member', () => {
    test.use({ storageState: personas.member.storageState });

    const notice = 'Non-troop-credit event: Does not count toward troop totals.';

    function card(page, name) {
        return page.locator('.event-cards .card').filter({ hasText: name });
    }

    test('sees which events do not count on the event list', async ({ page }) => {
        await page.goto('/events');

        //  an info icon where the unit logo would be, and the sentence in the card
        const uncounted = card(page, seeded.uncountedEvent);
        await expect(uncounted.locator('.card-header .not-counted-logo')).toBeVisible();
        await expect(uncounted.locator('.card-header img')).toHaveCount(0);
        await expect(uncounted.getByText(notice)).toBeVisible();

        const counted = card(page, seeded.openEvent);
        await expect(counted.locator('.not-counted-logo')).toHaveCount(0);
        await expect(counted.getByText(notice)).toHaveCount(0);
    });

    test('sees that an event of a non-counting type does not count', async ({ page }) => {
        await page.goto('/events');
        await page.getByRole('link', { name: seeded.uncountedEvent }).first().click();

        await expect(page.locator('.card-header').first().locator('.not-counted-logo')).toBeVisible();
        //  at the top of the details, and again above the roster where troopers sign up
        await expect(page.getByText(notice)).toHaveCount(2);
        await expect(page.getByText(notice).first()).toBeVisible();
    });

    test('sees no such notice on a regular event', async ({ page }) => {
        await page.goto('/events');
        await page.getByRole('link', { name: seeded.openEvent }).first().click();

        await expect(page.getByText('Mission Roster')).toBeVisible();
        await expect(page.getByText(notice)).toHaveCount(0);
        await expect(page.locator('.not-counted-logo')).toHaveCount(0);
    });

    test('sees which past troops did not count on their service record', async ({ page }) => {
        await page.goto('/events');
        await page.getByRole('button', { name: 'Service Records' }).click();
        await page.getByRole('link', { name: 'My Service', exact: true }).click();
        await page.getByRole('tab', { name: 'History' }).click();

        //  the history opens as a table on desktop
        const history = page.locator('#recent-shifts');
        const workshop = history.getByRole('row').filter({ hasText: seeded.pastUncountedEvent });
        await expect(workshop.getByText('Non-troop-credit')).toBeVisible();

        const hospital = history.getByRole('row').filter({ hasText: seeded.pastCountedEvent });
        await expect(hospital).toBeVisible();
        await expect(hospital.getByText('Non-troop-credit')).toHaveCount(0);
    });
});
