import { describe, expect, it, vi } from 'vitest';

const { post, visit, formPost } = vi.hoisted(() => ({ post: vi.fn(), visit: vi.fn(), formPost: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({
    router: { post, visit },
    useForm: (data: Record<string, unknown>) => ({ ...data, post: formPost, errors: {}, processing: false, isDirty: false }),
}));
vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, unknown> = {}) =>
        `/${name}?${new URLSearchParams(params as Record<string, string>).toString()}`,
}));

import {
    AssignTrooperViewModel,
    ListViewModel,
    TroopersViewModel,
} from '../../../../../../resources/svelte/pages/admin/awards/models/vms';
import type { Award } from '../../../../../../resources/svelte/pages/admin/awards/models/types';

const award: Award = {
    id: 5,
    organization_id: 4,
    organization_name: 'E2E Garrison',
    name: 'Trooper of the Year',
    frequency: 'annually',
    frequency_label: 'Annually',
};

describe('ListViewModel', () => {
    it('searches and keeps the organization filter', () => {
        const vm = new ListViewModel({
            awards: { data: [], meta: { links: [] } },
            search_term: '',
            organization: { id: 4, name: 'E2E Garrison' },
        });

        vm.search_term = ' year ';
        vm.search(new Event('submit'));

        expect(visit).toHaveBeenLastCalledWith('/admin.awards.list?search_term=year&organization_id=4');
    });
});

describe('TroopersViewModel', () => {
    it('asks before removing and posts the grant to remove', () => {
        const vm = new TroopersViewModel({
            award,
            troopers: [{ award_trooper_id: 9, display_name: 'TK-1', award_date: 'Jan 01, 2026' }],
        });

        vm.confirmRemove(vm.troopers[0]);
        vm.remove();

        expect(post).toHaveBeenLastCalledWith(
            '/admin.awards.remove-trooper?award=5',
            { remove_trooper_id: 9 },
            expect.any(Object),
        );
    });

    it('does nothing when nothing is being removed', () => {
        post.mockClear();
        const vm = new TroopersViewModel({ award, troopers: [] });

        vm.remove();

        expect(post).not.toHaveBeenCalled();
    });
});

describe('AssignTrooperViewModel', () => {
    it('sends the picked trooper with the award date', () => {
        const vm = new AssignTrooperViewModel({ award, award_date: '2026-01-01' });

        vm.trooper = { id: 12, legal_name: 'Jane', display_name: 'TK-12', email: '' };
        vm.submit(new Event('submit'));

        expect(vm.form.trooper_id).toBe(12);
        expect(formPost).toHaveBeenLastCalledWith('/admin.awards.assign-trooper?award=5');
    });
});
