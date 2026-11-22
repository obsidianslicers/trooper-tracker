import { beforeEach, describe, expect, it, vi } from 'vitest';

const { get } = vi.hoisted(() => ({ get: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { get } }));

import { ReportFiltersViewModel } from '../../../../../../resources/svelte/pages/admin/reports/models/vms/ReportFiltersViewModel.svelte';

describe('ReportFiltersViewModel', () => {
    beforeEach(() => {
        get.mockReset();
    });

    it('leaves empty filters out of the query and sends ticked boxes as 1', () => {
        const subject = new ReportFiltersViewModel(
            {
                organization_id: null,
                date_start: '2026-01-01',
                date_end: '',
                active_only: true,
                charity_only: false,
                organization_ids: [3, 4],
            },
            '/admin/reports/trooper-event-summary',
        );

        expect(subject.query()).toEqual({
            date_start: '2026-01-01',
            active_only: 1,
            organization_ids: [3, 4],
        });
    });

    it('reloads the report with what was picked', () => {
        const subject = new ReportFiltersViewModel(
            { organization_id: null as number | null },
            '/admin/reports/costume-event-summary',
        );
        const event = new Event('submit', { cancelable: true });

        subject.filters.organization_id = 7;
        subject.run(event);

        expect(event.defaultPrevented).toBe(true);
        expect(get).toHaveBeenCalledWith('/admin/reports/costume-event-summary', { organization_id: 7 });
    });

    it('keeps its own copy of the filters it was given', () => {
        const filters = { organization_id: 1 as number | null };
        const subject = new ReportFiltersViewModel(filters, '/report');

        subject.filters.organization_id = 2;

        expect(filters.organization_id).toBe(1);
    });
});
