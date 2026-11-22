// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/svelte', () => ({ page: { props: {} } }));

import { DashboardViewModel } from '../../../../../resources/svelte/pages/admin/models/vms/DashboardViewModel.svelte';

const counts = {
    not_approved: 2,
    pending_join_requests: 0,
    pending_costumes: 1,
    troops_awaiting_verification: 0,
    pending_permission_requests: 1,
};

describe('DashboardViewModel', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it('shows an alert for each count above what was dismissed', () => {
        const subject = new DashboardViewModel(counts, 0, 7, '/permissions');

        expect(subject.alerts.map((alert) => alert.key)).toEqual(['approvals', 'permission_requests']);
        expect(subject.alerts[0].text).toBe('There are 2 troopers ready for action!');
        expect(subject.alerts[1].href).toBe('/permissions');
    });

    it('remembers a dismissal under the key the old dashboard used', () => {
        const subject = new DashboardViewModel(counts, 0, 7, '/permissions');

        subject.dismiss(subject.alerts[0]);

        expect(localStorage.getItem('admin_dismissed_approvals_7')).toBe('2');
        expect(new DashboardViewModel(counts, 0, 7, '/p').alerts.map((a) => a.key))
            .toEqual(['permission_requests']);
    });

    it('brings a dismissed alert back once the count grows', () => {
        localStorage.setItem('admin_dismissed_approvals_7', '2');

        const subject = new DashboardViewModel({ ...counts, not_approved: 3 }, 0, 7, '/p');

        expect(subject.alerts[0].key).toBe('approvals');
    });

    it('describes waiting and clear queues on the cards', () => {
        const subject = new DashboardViewModel(counts, 0, 7, '/p');

        expect(subject.card('costume-approvals').lines[0]).toEqual({
            text: '1 costume awaiting review',
            tone: 'warning',
        });
        expect(subject.card('troop-verifications').lines[0]).toEqual({
            text: 'No troops waiting',
            tone: 'success',
        });
    });

    it('marks System Check when checks need attention', () => {
        const card = new DashboardViewModel(counts, 3, 7, '/p').card('system-check');

        expect(card.danger).toBe(true);
        expect(card.lines[1]).toEqual({ text: '3 checks need attention', tone: 'danger' });
        expect(new DashboardViewModel(counts, 0, 7, '/p').card('system-check').danger).toBe(false);
    });
});
