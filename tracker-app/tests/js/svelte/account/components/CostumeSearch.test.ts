// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post } = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { post }, usePage: () => ({ props: {} }) }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import CostumeSearch from '../../../../../resources/svelte/pages/account/components/CostumeSearch.svelte';
import { CostumesViewModel } from '../../../../../resources/svelte/pages/account/models/vms/CostumesViewModel.svelte';

const costume = (organizations: { id: number; name: string; selected: boolean }[]) => ({
    id: 3,
    name: 'Stormtrooper',
    organizations,
});

describe('CostumeSearch', () => {
    beforeEach(() => post.mockClear());

    it('shows search results to pick from', () => {
        const vm = new CostumesViewModel([]);
        vm.show_results = true;
        vm.search_results = [costume([])];

        render(CostumeSearch, { vm });

        expect(screen.getByRole('button', { name: 'Stormtrooper' })).toBeTruthy();
    });

    it('hides results until the search is shown', () => {
        const vm = new CostumesViewModel([]);
        vm.search_results = [costume([])];

        render(CostumeSearch, { vm });

        expect(screen.queryByRole('button', { name: 'Stormtrooper' })).toBeNull();
    });

    it('lets the trooper pick organizations after choosing a costume', async () => {
        const vm = new CostumesViewModel([]);
        vm.show_results = true;
        vm.search_results = [costume([{ id: 1, name: '501st Legion', selected: false }])];
        render(CostumeSearch, { vm });

        await fireEvent.click(screen.getByRole('button', { name: 'Stormtrooper' }));

        expect(screen.getByText('501st Legion')).toBeTruthy();
        expect(
            (screen.getByRole('button', { name: 'Add to Armory' }) as HTMLButtonElement).disabled,
        ).toBe(true);
    });

    it('adds the costume once an organization is checked', async () => {
        const vm = new CostumesViewModel([]);
        vm.selectCostume(costume([{ id: 1, name: '501st Legion', selected: false }]));
        render(CostumeSearch, { vm });

        await fireEvent.click(screen.getByRole('checkbox'));
        await fireEvent.click(screen.getByRole('button', { name: 'Add to Armory' }));

        expect(post).toHaveBeenCalledWith(
            '/account.add-costume',
            { costume_id: 3, organization_ids: [1] },
            expect.any(Object),
        );
    });

    it('warns when a costume has no organizations', () => {
        const vm = new CostumesViewModel([]);
        vm.selectCostume(costume([]));

        render(CostumeSearch, { vm });

        expect(screen.getByText(/No organizations found/)).toBeTruthy();
    });
});
