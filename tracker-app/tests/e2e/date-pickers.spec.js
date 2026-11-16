// Date and date-time fields: a calendar popup where the main pointer is a mouse, the phone's
// own pickers where it's a finger (resources/js/custom/date-picker.js).
import { test, expect, devices } from '@playwright/test';
import { personas } from './support/personas.js';

test.use({ storageState: personas.moderator.storageState });

const request = `Contact Name: Jane Requester
Event Name: Library Star Wars Day
Venue: Main Library
Event Start: 12/13/2030 - 0930
Event End: 12/13/2030 - 1300`;

async function openCreate(page) {
    await page.goto('/admin/events/create');
    await page.locator('#picker-container-organization_id').click();
    const modal = page.locator('#modal-picker');
    await modal.locator('li[data-name="501st Legion"]').click();
    await expect(modal).toBeHidden();
}

async function openSchedule(page) {
    await page.getByRole('button', { name: 'Schedule' }).click();
}

test.describe('with a mouse', () => {
    test('a date-time field opens a calendar, and picking a day fills it in', async ({ page }) => {
        await openCreate(page);
        await page.getByText('Input Manually').click();
        await openSchedule(page);

        const start = page.locator('#event_start');
        await expect(start).toHaveAttribute('type', 'text');
        await start.fill('2030-06-01 1:00PM');
        await page.locator('#name').click();

        await start.click();
        const calendar = page.locator('[data-vc="calendar"]').filter({ visible: true });
        await expect(calendar.locator('[data-vc="month"]')).toHaveText('June');
        await expect(calendar.locator('[data-vc-date="2030-06-01"]')).toHaveAttribute('data-vc-date-selected', /.*/);

        await calendar.locator('[data-vc-date="2030-06-15"] button').click();
        await expect(start).toHaveValue('2030-06-15 1:00PM');
    });
});

// a phone's screen and touch input; the browser type stays the configured one
const { defaultBrowserType: _browser, ...phone } = devices['Pixel 7'];

test.describe('on a touch screen', () => {
    test.use(phone);

    test('date-time fields are the phone\'s own, and a pasted request fills them in', async ({ page }) => {
        await openCreate(page);

        await page.locator('#source').fill(request);
        await page.locator('#source').blur();

        await openSchedule(page);
        await expect(page.locator('#event_start')).toHaveAttribute('type', 'datetime-local');
        await expect(page.locator('#event_start')).toHaveValue('2030-12-13T09:30');
        await expect(page.locator('#event_end')).toHaveValue('2030-12-13T13:00');
        await expect(page.locator('[data-vc="calendar"]')).toHaveCount(0);
    });
});
