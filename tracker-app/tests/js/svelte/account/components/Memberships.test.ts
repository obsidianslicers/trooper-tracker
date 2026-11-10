// @vitest-environment jsdom
import { render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/svelte', () => ({ useForm: vi.fn(), router: { post: vi.fn() } }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import MembershipDisplay from '../../../../../resources/svelte/pages/account/components/MembershipDisplay.svelte';
import MembershipRequests from '../../../../../resources/svelte/pages/account/components/MembershipRequests.svelte';

const vmWith = (overrides: Record<string, unknown>) => ({
    organization_memberships: [],
    organization_requests: [],
    ...overrides,
});

const membership = (membership_status: string) => ({
    membership_path: '501st Legion > Garrison',
    membership_status,
    identifier: 'TK-1',
    image_url: '/501.png',
});

const request = (status: string, denial_reason = '') => ({
    membership_path: 'Rebel Legion > Base',
    status,
    created: '2026-01-01',
    updated: '2026-01-02',
    identifier: 'RL-1',
    denial_reason,
    image_url: '/rl.png',
});

describe('MembershipDisplay', () => {
    it('tells the trooper when there are no memberships', () => {
        render(MembershipDisplay, { vm: vmWith({}) as any });

        expect(screen.getByText(/No organization memberships/)).toBeTruthy();
    });

    it('lists each membership', () => {
        const vm = vmWith({ organization_memberships: [membership('active')] });

        render(MembershipDisplay, { vm: vm as any });

        expect(screen.getByText('501st Legion > Garrison')).toBeTruthy();
        expect(screen.queryByText(/No organization memberships/)).toBeNull();
    });
});

describe('MembershipRequests', () => {
    it('shows nothing when there are no requests', () => {
        render(MembershipRequests, { vm: vmWith({}) as any });

        expect(screen.queryByText('Organization Requests')).toBeNull();
    });

    it('lists requests with their status and last update', () => {
        const vm = vmWith({ organization_requests: [request('pending')] });

        render(MembershipRequests, { vm: vm as any });

        expect(screen.getByText('Rebel Legion > Base')).toBeTruthy();
        expect(screen.getByText('pending')).toBeTruthy();
        expect(screen.getByText('updated 2026-01-02')).toBeTruthy();
    });

    it('explains why a request was denied', () => {
        const vm = vmWith({ organization_requests: [request('denied', 'Missing proof')] });

        render(MembershipRequests, { vm: vm as any });

        expect(screen.getByText('Missing proof')).toBeTruthy();
    });

    it('does not show a denial reason for requests that were not denied', () => {
        const vm = vmWith({ organization_requests: [request('pending', 'Stale reason')] });

        render(MembershipRequests, { vm: vm as any });

        expect(screen.queryByText('Stale reason')).toBeNull();
    });
});
