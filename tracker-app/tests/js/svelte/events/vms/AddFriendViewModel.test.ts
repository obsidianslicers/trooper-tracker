import { afterEach, describe, expect, it, vi } from 'vitest';
import { AddFriendViewModel } from '../../../../../resources/svelte/pages/events/models/vms/AddFriendViewModel.svelte';

const friend = {
    trooper: { id: 12, name: 'TK-12' },
    organizations: [],
    stations: [],
    requires_costume: true,
    costumes: [{ value: '3', label: 'Sandtrooper' }],
    default_costume: '3',
};

describe('AddFriendViewModel', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('loads the picked trooper\'s choices and signs them up', async () => {
        const fetch = vi.fn().mockResolvedValue({ ok: true, json: async () => friend });
        vi.stubGlobal('fetch', fetch);
        const vm = new AddFriendViewModel('/events/add-trooper-form/4');

        await vm.picked({ id: 12 });

        expect(fetch.mock.calls[0][0]).toBe('/events/add-trooper-form/4?trooper_id=12');
        expect(vm.friend?.trooper.name).toBe('TK-12');
        expect(vm.payload()).toEqual({ trooper_id: 12, costume_id: '3' });
        expect(vm.is_loading).toBe(false);

        vm.reset();
        expect(vm.form).toBeNull();
    });

    it('says so when the choices can\'t load', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, status: 500 }));
        const vm = new AddFriendViewModel('/events/add-trooper-form/4');

        await vm.picked({ id: 12 });

        expect(vm.form).toBeNull();
        expect(vm.error).not.toBe('');
    });
});
