// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import { afterEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/svelte', () => ({ router: { post: vi.fn() }, page: { props: {} }, usePage: () => ({ props: {} }) }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));
vi.mock('bootstrap', () => ({
    Modal: class {
        show() {}
        hide() {}
        dispose() {}
    },
}));

import Trooper from '../../../../../resources/svelte/pages/service-records/Trooper.svelte';
import type { RecentShift, ServiceRecordPageData } from '../../../../../resources/svelte/pages/service-records/models/record-types';

const recent = (overrides: Partial<RecentShift> = {}): RecentShift => ({
    id: 1,
    event_name: 'Hospital visit',
    event_url: '/events/1',
    date: 'Jan 5, 2026',
    time: '10:00 AM - 12:00 PM',
    logo_url: '/logo.png',
    costume: 'TK Classic',
    is_handler: false,
    credited_to: ['501st Legion'],
    not_counted: null,
    same_day_repeat: null,
    ...overrides,
});

const props = (overrides: Partial<ServiceRecordPageData> = {}): ServiceRecordPageData => ({
    trooper: {
        id: 7,
        display_name: 'TK-421',
        rank_title: 'Recruit',
        rank_theme: 'secondary',
        is_departed: false,
        is_handler: false,
        is_self: true,
        edit_url: null,
        missing_credit_url: null,
    },
    summary: {
        total_shifts: '12',
        total_hours: '30',
        theme: 'Star Wars',
        clubs: [{ name: '501st Legion', troops: '12' }],
        rank: 3,
        direct_funds: '-',
        indirect_funds: '$1.2K',
        total_donated: null,
        donation_months: null,
        milestones: [{ description: '10 troops', icon: 'fa-star', is_501st: true }],
        donor_image_url: null,
    },
    upcoming_shifts: [],
    recent_shifts: [recent()],
    pending_shifts: [],
    awards: [{ id: 1, name: 'Trooper of the Year', date: 'Dec 1, 2025' }],
    organizations: [],
    costumes: [],
    tagged_uploads: [],
    donations: null,
    donations_total: null,
    credit_requests: null,
    record_slots: {},
    ...overrides,
});

const tab = (name: string) => screen.getByRole('tab', { name });

describe('Trooper service record', () => {
    afterEach(() => localStorage.clear());

    it('shows the name, rank, summary and milestones', () => {
        render(Trooper, props());

        expect(screen.getByRole('heading', { level: 2 }).textContent).toContain('TK-421');
        expect(screen.getByText('// Recruit')).toBeTruthy();
        expect(screen.getByText('10 troops')).toBeTruthy();
        expect(screen.getByText('$1.2K')).toBeTruthy();
    });

    it('offers Edit and Fix Missing Credit only when the page sends their links', () => {
        const { unmount } = render(Trooper, props());
        expect(screen.queryByText('Edit')).toBeNull();
        unmount();

        render(Trooper, props({
            trooper: { ...props().trooper, edit_url: '/admin/troopers/7', missing_credit_url: '/fix' },
        }));
        expect(screen.getByText('Edit').closest('a')?.getAttribute('href')).toBe('/admin/troopers/7');
        expect(screen.getByText('Fix Missing Credit').closest('a')?.getAttribute('href')).toBe('/fix');
    });

    it('opens on Upcoming and moves between tabs', async () => {
        render(Trooper, props());

        expect(tab('Upcoming').getAttribute('aria-selected')).toBe('true');
        expect(screen.getByText('No Upcoming Shifts ... Yet!')).toBeTruthy();

        await fireEvent.click(tab('History'));
        expect(document.querySelector('#recent-shifts')?.textContent).toContain('Hospital visit');
        expect(screen.getByText('501st Legion', { selector: '.badge' })).toBeTruthy();

        await fireEvent.click(tab('Awards'));
        expect(screen.getByText('Trooper of the Year')).toBeTruthy();
    });

    it('shows a departed trooper in memoriam, on History, with no Upcoming tab', () => {
        render(Trooper, props({ trooper: { ...props().trooper, is_departed: true } }));

        expect(screen.getByText('"The Force will be with you, always."')).toBeTruthy();
        expect(screen.queryByRole('tab', { name: 'Upcoming' })).toBeNull();
        expect(tab('History').getAttribute('aria-selected')).toBe('true');
    });

    it('marks a past shift that added no troop', async () => {
        render(Trooper, props({
            recent_shifts: [recent({ not_counted: { label: 'Non-troop-credit', hint: 'Does not count.' } })],
        }));

        await fireEvent.click(tab('History'));

        expect(screen.getByText('Non-troop-credit').getAttribute('title')).toBe('Does not count.');
    });

    it('asks the trooper about shifts waiting for confirmation', async () => {
        render(Trooper, props({
            pending_shifts: [{ id: 3, event_name: 'Parade', event_url: '/events/3', date: 'Jan 1', time: '9 AM', made_it_url: '/made', missed_it_url: '/missed' }],
        }));

        await fireEvent.click(tab('History'));

        expect(screen.getByText('1 shift needs confirmation')).toBeTruthy();
        expect(screen.getByText('Made It').getAttribute('href')).toBe('/made');
        expect(screen.getByText('Missed It').getAttribute('href')).toBe('/missed');
    });

    it('has a Support tab with the donations only when support is on', async () => {
        const { unmount } = render(Trooper, props());
        expect(screen.queryByRole('tab', { name: 'Support' })).toBeNull();
        unmount();

        render(Trooper, props({ donations: [{ id: 1, date: 'Jan 1, 2026', amount: '10.00' }], donations_total: '10.00' }));
        await fireEvent.click(tab('Support'));

        expect(screen.getAllByText('$ 10.00')).toHaveLength(2);
    });

    it('has a Credit Requests tab with each request and its outcome only when the page sends them', async () => {
        const { unmount } = render(Trooper, props());
        expect(screen.queryByRole('tab', { name: 'Credit Requests' })).toBeNull();
        unmount();

        render(Trooper, props({
            credit_requests: [
                { id: 2, event_name: 'Parade of Lights', troop_date: 'Sep 29, 2026', requested_on: 'Nov 27, 2026', status: 'denied', decided_on: 'Nov 28, 2026', url: '/events/troop-corrections/2' },
                { id: 1, event_name: 'Library Day', troop_date: 'Sep 1, 2026', requested_on: 'Nov 20, 2026', status: 'awaiting_coordinator', decided_on: null, url: '/events/troop-corrections/1' },
            ],
        }));
        await fireEvent.click(tab('Credit Requests'));

        expect(screen.getByText('Parade of Lights').getAttribute('href')).toBe('/events/troop-corrections/2');
        expect(screen.getByText('Denied')).toBeTruthy();
        expect(screen.getByText('Waiting for the event coordinator')).toBeTruthy();
        expect(screen.getByText('Request Troop Credit').getAttribute('href')).toBe('/events.troop-correction-form');
    });

    it('shows a handler note instead of costumes', async () => {
        render(Trooper, props({ trooper: { ...props().trooper, is_handler: true } }));

        await fireEvent.click(tab('Costumes'));

        expect(screen.getByText(/Handlers don't typically have costumes/)).toBeTruthy();
    });

    it('shows a tagged photo large when clicked', async () => {
        render(Trooper, props({ tagged_uploads: [{ id: 1, small_url: '/small.jpg', large_url: '/large.jpg' }] }));

        await fireEvent.click(tab('Photos'));
        await fireEvent.click(document.querySelector('img[src="/small.jpg"]')!.closest('a')!);
        await tick();

        const shown = screen.getAllByAltText('Service record photo').map((img) => img.getAttribute('src'));
        expect(shown).toContain('/large.jpg');
    });

    it('puts plugin markup in its slots', () => {
        render(Trooper, props({ record_slots: { 'service-records.trooper.badges': '<span class="plugin-badge">Forum</span>' } }));

        expect(document.querySelector('[data-plugin-slot="service-records.trooper.badges"] .plugin-badge')).toBeTruthy();
    });
});
