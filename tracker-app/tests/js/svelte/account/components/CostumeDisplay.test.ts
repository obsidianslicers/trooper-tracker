// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post } = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { post }, usePage: () => ({ props: {} }) }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import CostumeDisplay from '../../../../../resources/svelte/pages/account/components/CostumeDisplay.svelte';
import { CostumesViewModel } from '../../../../../resources/svelte/pages/account/models/vms/CostumesViewModel.svelte';

const trooperCostume = (pending: string[] = []) => ({
    costume_id: 3,
    name: 'Stormtrooper',
    costume_organizations: '501st Legion',
    pending_organizations: pending,
    submitting: false,
});

describe('CostumeDisplay', () => {
    beforeEach(() => post.mockClear());

    it('invites the trooper to attach a costume when there are none', () => {
        render(CostumeDisplay, { vm: new CostumesViewModel([]) });

        expect(screen.getByText(/No Attached Costumes/)).toBeTruthy();
    });

    it('lists attached costumes and their organizations', () => {
        render(CostumeDisplay, { vm: new CostumesViewModel([trooperCostume()]) });

        expect(screen.getByText('Stormtrooper')).toBeTruthy();
        expect(screen.getByText('501st Legion')).toBeTruthy();
        expect(screen.queryByText(/Pending officer review/)).toBeNull();
    });

    it('flags organizations awaiting officer review', () => {
        const vm = new CostumesViewModel([trooperCostume(['Rebel Legion'])]);

        render(CostumeDisplay, { vm });

        expect(screen.getByText(/Pending officer review: Rebel Legion/)).toBeTruthy();
    });

    it('removes a costume from the armory', async () => {
        render(CostumeDisplay, { vm: new CostumesViewModel([trooperCostume()]) });

        await fireEvent.click(screen.getByRole('button'));

        expect(post).toHaveBeenCalledWith(
            '/account.remove-costume',
            { costume_id: 3 },
            expect.any(Object),
        );
    });
});
