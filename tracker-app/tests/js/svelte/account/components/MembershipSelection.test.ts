// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { form } = vi.hoisted(() => ({
    form: {
        organization_id: null as number | null,
        identifier: null as string | null,
        post: vi.fn(),
        defaults: vi.fn(),
        processing: false,
        isDirty: false,
        errors: {} as Record<string, string[]>,
    },
}));

vi.mock('@inertiajs/svelte', () => ({ useForm: () => form, router: { post: vi.fn() } }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import Costumes from '../../../../../resources/svelte/pages/account/components/Costumes.svelte';
import Memberships from '../../../../../resources/svelte/pages/account/components/Memberships.svelte';
import MembershipSelection from '../../../../../resources/svelte/pages/account/components/MembershipSelection.svelte';
import { MembershipsViewModel } from '../../../../../resources/svelte/pages/account/models/vms/MembershipsViewModel.svelte';

const memberships = (identifier_validation = '^\\d+$') => ({
    organizations: [
        {
            id: 1,
            name: '501st Legion',
            identifier_display: 'TK Number',
            identifier_validation,
            regions: [],
        },
    ],
    organization_options: [
        { value: 1, label: '501st Legion' },
        { value: 2, label: 'Rebel Legion' },
    ],
    organization_memberships: [],
    organization_requests: [],
});

describe('MembershipSelection', () => {
    beforeEach(() => {
        form.organization_id = null;
        form.identifier = null;
        form.isDirty = false;
        form.errors = {};
        form.post.mockClear();
    });

    it('offers the organizations to join', () => {
        const vm = new MembershipsViewModel(false, memberships());

        render(MembershipSelection, { vm });

        expect(screen.getByLabelText('Organization to Join')).toBeTruthy();
        expect(screen.getByRole('option', { name: 'Rebel Legion' })).toBeTruthy();
    });

    it('does not offer a request until an organization is chosen', () => {
        const vm = new MembershipsViewModel(false, memberships());

        render(MembershipSelection, { vm });

        expect(screen.queryByRole('button', { name: 'Request Access' })).toBeNull();
    });

    it('asks for the member ID when the organization uses one', () => {
        form.organization_id = 1;
        const vm = new MembershipsViewModel(false, memberships());

        render(MembershipSelection, { vm });

        expect(screen.getByLabelText('501st Legion TK Number')).toBeTruthy();
    });

    it('skips the member ID when the organization has no identifier', () => {
        form.organization_id = 1;
        const vm = new MembershipsViewModel(false, memberships(''));

        render(MembershipSelection, { vm });

        expect(screen.queryByLabelText('501st Legion TK Number')).toBeNull();
    });

    it('describes visiting for visitors', () => {
        const vm = new MembershipsViewModel(true, memberships());

        render(MembershipSelection, { vm });

        expect(screen.getByText(/Choose the organization you are visiting/)).toBeTruthy();
    });

    it('submits the request', async () => {
        form.organization_id = 1;
        form.isDirty = true;
        const vm = new MembershipsViewModel(false, memberships());
        render(MembershipSelection, { vm });

        await fireEvent.click(screen.getByRole('button', { name: 'Request Access' }));

        expect(form.post).toHaveBeenCalledWith('/account.add-trooper-request', expect.any(Object));
    });
});

describe('Memberships', () => {
    it('shows memberships, requests and the join form together', () => {
        render(Memberships, { is_visitor: false, memberships: memberships() });

        expect(screen.getByText('Organization Memberships')).toBeTruthy();
        expect(screen.getByText('Organization Join Requests')).toBeTruthy();
    });
});

describe('Costumes', () => {
    it('shows the search and the armory together', () => {
        render(Costumes, { costumes: [] });

        expect(screen.getByLabelText('Find Your Costume')).toBeTruthy();
        expect(screen.getByText(/No Attached Costumes/)).toBeTruthy();
    });
});
