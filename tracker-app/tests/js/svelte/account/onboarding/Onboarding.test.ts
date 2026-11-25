// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import { describe, expect, it, vi } from 'vitest';

const { formPost } = vi.hoisted(() => ({ formPost: vi.fn() }));

vi.mock('@inertiajs/svelte', async () => {
    const { proxyState } = await import('./proxy-state.svelte');

    return {
        router: { post: vi.fn() },
        page: { props: {} },
        useForm: (data: Record<string, unknown>) => proxyState({
            ...data,
            post: formPost,
            errors: {},
            processing: false,
            isDirty: false,
            defaults: vi.fn(),
        }),
    };
});
vi.mock('$lib/utils', () => ({ getRoute: (name: string) => `/${name}` }));

import Denied from '../../../../../resources/svelte/pages/account/Denied.svelte';
import Setup from '../../../../../resources/svelte/pages/account/Setup.svelte';
import type { DeniedPageData, SetupPageData } from '../../../../../resources/svelte/pages/account/models/onboarding-types';
import { DeniedViewModel } from '../../../../../resources/svelte/pages/account/models/vms/DeniedViewModel.svelte';

const legion = {
    id: 1,
    name: '501st Legion',
    identifier_display: 'TKID',
    requires_guardian: false,
    regions: [
        { id: 10, name: 'Florida Garrison', units: [{ id: 100, name: 'Squad A' }, { id: 101, name: 'Squad B' }] },
        { id: 11, name: 'Georgia Garrison', units: [] },
    ],
};

const rebels = {
    id: 2,
    name: 'Rebel Legion',
    identifier_display: 'RL ID',
    requires_guardian: false,
    regions: [{ id: 20, name: 'Echo Base', units: [{ id: 200, name: 'Hoth' }] }],
};

const denied = (overrides: Partial<DeniedPageData> = {}): DeniedPageData => ({
    denial_reason: 'No photos.',
    denied_requests: [{ id: 5, club: '501st Legion › Florida Garrison › Squad A', organization_id: 1, identifier: '1234', region_id: 10, unit_id: 100 }],
    organizations: [legion, rebels],
    account_type: 'member',
    ...overrides,
});

describe('DeniedViewModel', () => {
    it('starts with the denied clubs picked, as they were asked for', () => {
        const vm = new DeniedViewModel(denied());

        expect(vm.form.organizations[1]).toEqual({ selected: true, identifier: '1234', region_id: 10, unit_id: 100 });
        expect(vm.form.organizations[2].selected).toBe(false);
    });

    it('picks the only region and unit of a club, and clears them when unpicked', () => {
        const vm = new DeniedViewModel(denied());

        vm.form.organizations[2].selected = true;
        vm.toggleOrganization(2);
        expect(vm.form.organizations[2]).toMatchObject({ region_id: 20, unit_id: 200 });

        vm.form.organizations[2].selected = false;
        vm.toggleOrganization(2);
        expect(vm.form.organizations[2]).toMatchObject({ region_id: null, unit_id: null });
    });

    it('offers the units of the region picked, and a new region drops the unit', () => {
        const vm = new DeniedViewModel(denied());

        expect(vm.getUnits(1).map((o) => o.label)).toEqual(['Squad A', 'Squad B']);

        vm.form.organizations[1].region_id = 11;
        vm.resetOnRegionChange(1);
        expect(vm.form.organizations[1].unit_id).toBeNull();
        expect(vm.getUnits(1)).toEqual([]);
    });

    it('resubmits to the resubmit route', () => {
        const vm = new DeniedViewModel(denied());

        vm.submit(new Event('submit'));

        expect(formPost).toHaveBeenCalledWith('/account.denied.resubmit');
    });
});

describe('Denied page', () => {
    it('shows why and what was asked for, with the club form filled in', async () => {
        render(Denied, { props: denied() });
        await tick();

        expect(screen.getByText('Your registration was not approved.')).toBeTruthy();
        expect(screen.getByText('No photos.')).toBeTruthy();
        expect(screen.getByText('501st Legion › Florida Garrison › Squad A')).toBeTruthy();
        expect((screen.getByLabelText('501st Legion') as HTMLInputElement).checked).toBe(true);
        expect((screen.getByLabelText('TKID') as HTMLInputElement).value).toBe('1234');
    });

    it('asks a visitor for no region or unit', async () => {
        render(Denied, { props: denied({ account_type: 'visitor' }) });
        await tick();

        expect(screen.queryByLabelText('Region/Garrison')).toBeNull();
    });

    it('lets a club be picked from the list', async () => {
        render(Denied, { props: denied({ denied_requests: [] }) });

        await fireEvent.click(screen.getByLabelText('Rebel Legion'));

        expect(screen.getByLabelText('RL ID')).toBeTruthy();
    });
});

describe('Setup page', () => {
    const setup: SetupPageData = {
        trooper: {
            email: 'tk@example.com',
            legal_name: 'Ben Smith',
            theme: 'stormtrooper',
            notification_frequency: 'daily',
            theme_options: [{ value: 'stormtrooper', label: 'Stormtrooper' }, { value: 'sith', label: 'Sith' }],
            frequency_options: [{ value: 'daily', label: 'Daily' }, { value: 'never', label: 'Never' }],
        },
        memberships: [{ id: 1, name: '501st Legion', assignment: 'Florida Garrison' }],
    };

    it('starts with the trooper details and lists their clubs', () => {
        render(Setup, { props: setup });

        expect((screen.getByLabelText('Email:') as HTMLInputElement).value).toBe('tk@example.com');
        expect((screen.getByLabelText('Legal Name:') as HTMLInputElement).value).toBe('Ben Smith');
        expect((screen.getByLabelText('Theme:') as HTMLSelectElement).value).toBe('stormtrooper');
        expect(screen.getByText('501st Legion')).toBeTruthy();
    });

    it('posts the form to the setup route', async () => {
        render(Setup, { props: setup });

        await fireEvent.click(screen.getByRole('button', { name: 'Update' }));

        expect(formPost).toHaveBeenCalledWith('/account.setup-submit');
    });
});
