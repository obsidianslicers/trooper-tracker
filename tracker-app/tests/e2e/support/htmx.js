// HTMX wires up swapped-in content only once it settles, a few milliseconds after the new
// HTML is visible. A test that changes a select in that gap changes it with no request
// behind it, so wait for HTMX to finish first.
export async function htmxSettled(page) {
    await page.waitForFunction(
        () => !document.querySelector('.htmx-request, .htmx-swapping, .htmx-settling, .htmx-added'),
    );
}
