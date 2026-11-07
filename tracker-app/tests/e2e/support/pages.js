import { expect } from '@playwright/test';

// Opens a page and fails on a server error or an uncaught script error. A page that renders
// but throws in the browser (a broken Svelte component, a missing Alpine helper) still fails.
export async function expectPageLoads(page, url) {
    const errors = [];
    const onError = (error) => errors.push(error.message);

    page.on('pageerror', onError);

    try {
        const response = await page.goto(url);

        expect(response?.status(), `${url} status`).toBeLessThan(400);
        await page.waitForLoadState('networkidle');
        expect(errors, `${url} script errors`).toEqual([]);
    } finally {
        page.off('pageerror', onError);
    }
}

export async function expectForbidden(page, url) {
    const response = await page.goto(url);

    expect(response?.status(), `${url} status`).toBe(403);
}
