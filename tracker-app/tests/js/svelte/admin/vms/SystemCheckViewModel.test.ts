// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { get } = vi.hoisted(() => ({ get: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { get } }));
vi.mock('$lib/utils', () => ({
    getRoute: (name: string, params: Record<string, number>) => `/${name}?errors=${params.errors}`,
}));

import { SystemCheckViewModel } from '../../../../../resources/svelte/pages/admin/models/vms/SystemCheckViewModel.svelte';

describe('SystemCheckViewModel', () => {
    beforeEach(() => {
        get.mockReset();
    });

    it('opens and closes an error', () => {
        const subject = new SystemCheckViewModel();

        subject.toggle(2);
        expect(subject.open[2]).toBe(true);

        subject.toggle(2);
        expect(subject.open[2]).toBe(false);
    });

    it('asks for the next page of errors without running the checks again', () => {
        const subject = new SystemCheckViewModel();

        subject.showMore(25);

        expect(subject.loading).toBe(true);
        expect(get).toHaveBeenCalledWith(
            '/admin.system-check?errors=50',
            {},
            expect.objectContaining({ only: ['log_entries', 'has_more_errors'] }),
        );

        get.mock.calls[0][2].onFinish();
        expect(subject.loading).toBe(false);
    });
});
