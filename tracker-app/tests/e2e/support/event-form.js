// Create Event: the host is picked from a searchable list of organizations.
import { expect } from '@playwright/test';

export async function pickHost(page, name) {
    await page.getByLabel('Hosting Organization', { exact: true }).click();
    const picker = page.getByRole('dialog');
    await picker.getByRole('textbox').fill(name);
    await picker.getByRole('button', { name, exact: true }).click();
    await expect(picker).toBeHidden();
}

export function field(page, label) {
    return page.getByLabel(label, { exact: true });
}
