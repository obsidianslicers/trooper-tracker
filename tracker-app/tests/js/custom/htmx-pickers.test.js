// @vitest-environment jsdom
import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import * as bootstrap from 'bootstrap';

beforeAll(async () => {
    window.bootstrap = bootstrap;
    await import('../../../resources/js/custom/htmx-pickers.js');
});

beforeEach(() => {
    vi.useFakeTimers();
    document.body.innerHTML = `
        <div id="picker-container-organization_id">
            <input type="hidden" name="organization_id">
            <input type="text" name="picker-organization_id">
        </div>
        <div class="modal fade modal-picker" id="modal-picker" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-body">
                <ul><li data-property="organization_id" data-id="7" data-name="501st Legion">501st Legion</li></ul>
            </div></div></div>
        </div>`;
});

afterEach(() => {
    vi.useRealTimers();
});

const modalEl = () => document.getElementById('modal-picker');
const pick = () => document.querySelector('li[data-id="7"]').click();

describe('picking a record in the picker modal', () => {
    it('fills in the picker and closes the modal', () => {
        bootstrap.Modal.getOrCreateInstance(modalEl()).show();
        vi.advanceTimersByTime(1000);

        pick();
        vi.advanceTimersByTime(1000);

        expect(document.querySelector('[name="organization_id"]').value).toBe('7');
        expect(document.querySelector('[name="picker-organization_id"]').value).toBe('501st Legion');
        expect(modalEl().classList.contains('show')).toBe(false);
    });

    it('still closes the modal when the pick comes while it is fading in', () => {
        bootstrap.Modal.getOrCreateInstance(modalEl()).show();
        // Past the backdrop, so the list shows, but before the modal's own fade-in is done.
        vi.advanceTimersByTime(6);
        expect(modalEl().classList.contains('show')).toBe(true);

        pick();
        vi.advanceTimersByTime(1000);

        expect(modalEl().classList.contains('show')).toBe(false);
        expect(modalEl().style.display).toBe('none');
    });

    it('does not close the modal again the next time it opens', () => {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl());
        modal.show();
        vi.advanceTimersByTime(1000);
        pick();
        vi.advanceTimersByTime(1000);

        modal.show();
        vi.advanceTimersByTime(1000);

        expect(modalEl().classList.contains('show')).toBe(true);
    });
});
