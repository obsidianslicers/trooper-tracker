import { describe, expect, it, vi } from 'vitest';

vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, number>) => `/${name}/${params.trooper}`,
}));

import { FriendsViewModel } from '../../../../../resources/svelte/pages/account/models/vms/FriendsViewModel.svelte';
import { MinorsViewModel } from '../../../../../resources/svelte/pages/account/models/vms/MinorsViewModel.svelte';

const trooper = { id: 7, legal_name: 'Ben Kenobi', display_name: 'Obi-Wan' };

describe.each([
    ['FriendsViewModel', (data: any) => new FriendsViewModel(data), 'friends'],
    ['MinorsViewModel', (data: any) => new MinorsViewModel(data), 'minors'],
])('%s', (_name, create, property) => {
    it('exposes the troopers it was given', () => {
        const subject: any = create([trooper]);

        expect(subject[property]).toEqual([trooper]);
    });

    it('falls back to an empty list without page data', () => {
        const subject: any = create(null);

        expect(subject[property]).toEqual([]);
    });

    it('links to the trooper service record', () => {
        const subject: any = create([trooper]);

        expect(subject.getServiceRecordUrl(trooper)).toBe('/service-records.trooper/7');
    });
});
