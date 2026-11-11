import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post, success } = vi.hoisted(() => ({ post: vi.fn(), success: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { post }, useForm: vi.fn() }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));
vi.mock('../../../../../resources/svelte/lib/states/toast-state.svelte', () => ({
    default: { success },
}));

import {
    CostumesViewModel,
    type Costume,
    type TrooperCostume,
} from '../../../../../resources/svelte/pages/account/models/vms/CostumesViewModel.svelte';

const costume = (): Costume => ({
    id: 3,
    name: 'Stormtrooper',
    organizations: [
        { id: 1, name: '501st', selected: true },
        { id: 2, name: 'Rebel Legion', selected: false },
    ],
});

const trooperCostume = (): TrooperCostume => ({
    costume_id: 3,
    name: 'Stormtrooper',
    costume_organizations: '501st',
    pending_organizations: [],
    submitting: false,
});

describe('CostumesViewModel', () => {
    beforeEach(() => {
        post.mockClear();
        success.mockClear();
    });

    it('falls back to an empty list without page data', () => {
        const subject = new CostumesViewModel(null as any);

        expect(subject.trooper_costumes).toEqual([]);
    });

    it('selecting a costume clears organization picks and hides results', () => {
        const subject = new CostumesViewModel([]);
        subject.show_results = true;

        subject.selectCostume(costume());

        expect(subject.selected_costume?.organizations.some((org) => org.selected)).toBe(false);
        expect(subject.search_term).toBe('Stormtrooper');
        expect(subject.show_results).toBe(false);
    });

    it('can only add a costume with at least one organization picked', () => {
        const subject = new CostumesViewModel([]);

        expect(subject.canAddCostume()).toBe(false);

        subject.selectCostume(costume());
        expect(subject.canAddCostume()).toBe(false);

        subject.selected_costume!.organizations[0].selected = true;
        expect(subject.canAddCostume()).toBe(true);
    });

    it('adds the selected costume with its chosen organizations', () => {
        const subject = new CostumesViewModel([]);
        subject.selectCostume(costume());
        subject.selected_costume!.organizations[1].selected = true;

        subject.addCostume();

        expect(post).toHaveBeenCalledWith(
            '/account.add-costume',
            { costume_id: 3, organization_ids: [2] },
            expect.any(Object),
        );
        expect(subject.submitting).toBe(true);
    });

    it('refreshes the list and notifies once a costume is added', () => {
        const subject = new CostumesViewModel([]);
        subject.selectCostume(costume());
        subject.addCostume();

        post.mock.calls[0][2].onSuccess({
            props: { results: { trooper_costumes: [trooperCostume()] } },
        });

        expect(subject.trooper_costumes).toEqual([trooperCostume()]);
        expect(subject.selected_costume).toBeNull();
        expect(subject.submitting).toBe(false);
        expect(success).toHaveBeenCalled();
    });

    it('removes a costume and refreshes the list', () => {
        const owned = trooperCostume();
        const subject = new CostumesViewModel([owned]);

        subject.removeCostume(owned);

        expect(post).toHaveBeenCalledWith(
            '/account.remove-costume',
            { costume_id: 3 },
            expect.any(Object),
        );

        post.mock.calls[0][2].onSuccess({ props: { results: {} } });

        expect(subject.trooper_costumes).toEqual([]);
        expect(success).toHaveBeenCalledWith('Stormtrooper removed successfully.');
    });
});
