// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from 'vitest';

//  jsdom has no Web Animations, which the toasts' Svelte transitions use.
Element.prototype.animate ??= () => ({ cancel() {}, finished: Promise.resolve(), onfinish: null });

async function loadWithPage(html) {
    vi.resetModules();
    document.body.innerHTML = html;

    return (await import('../../../resources/js/custom/toasts.js')).default;
}

describe('toasts on Blade pages', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('shows the page flash messages as toasts', async () => {
        const toasts = await loadWithPage(`<script type="application/json" id="tt-flash">
            [{"type":"success","message":"Costume created."},{"type":"danger","message":"Nope."}]
        </script>`);

        expect(toasts.all.map(({ type, text }) => [type, text])).toEqual([
            ['success', 'Costume created.'],
            ['danger', 'Nope.'],
        ]);
    });

    it('shows nothing without page flash messages', async () => {
        const toasts = await loadWithPage('<main></main>');

        expect(toasts.all).toEqual([]);
    });

    it('ignores a type it does not know', async () => {
        const toasts = await loadWithPage(`<script type="application/json" id="tt-flash">
            [{"type":"show","message":"Odd."}]
        </script>`);

        expect(toasts.all).toEqual([]);
    });
});
