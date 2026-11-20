// @vitest-environment jsdom
import { describe, expect, it, vi } from 'vitest';
import { bindTooltips } from '../../../resources/js/custom/tooltips.js';

describe('bindTooltips', () => {
    it('creates a tooltip for each tooltip trigger under the root', () => {
        document.body.innerHTML = `
            <span id="a" data-bs-toggle="tooltip" title="A"></span>
            <span id="b" data-bs-toggle="tooltip" title="B"></span>
            <span id="c" title="not a tooltip"></span>`;
        const tooltip = { getOrCreateInstance: vi.fn() };

        bindTooltips(document, tooltip);

        const ids = tooltip.getOrCreateInstance.mock.calls.map(([el]) => el.id);
        expect(ids).toEqual(['a', 'b']);
    });
});
