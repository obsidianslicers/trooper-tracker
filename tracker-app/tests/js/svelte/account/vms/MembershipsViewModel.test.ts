import { beforeEach, describe, expect, it, vi } from 'vitest';

const { form, success } = vi.hoisted(() => ({
    form: {
        organization_id: null as number | null,
        identifier: null as string | null,
        post: vi.fn(),
        defaults: vi.fn(),
    },
    success: vi.fn(),
}));

vi.mock('@inertiajs/svelte', () => ({ useForm: () => form, router: { post: vi.fn() } }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));
vi.mock('../../../../../resources/svelte/lib/states/toast-state.svelte', () => ({
    default: { success },
}));

import {
    MembershipsViewModel,
    type MembershipsPageData,
} from '../../../../../resources/svelte/pages/account/models/vms/MembershipsViewModel.svelte';

const pageData = (): MembershipsPageData => ({
    organizations: [
        {
            id: 1,
            name: '501st Legion',
            identifier_display: 'TK Number',
            identifier_validation: '^\\d+$',
            regions: [
                {
                    id: 10,
                    name: 'Garrison',
                    parent_id: 1,
                    primary_organization_id: 1,
                    units: [{ id: 100, name: 'Squad', parent_id: 10, primary_organization_id: 1 }],
                },
            ],
        },
    ],
    organization_options: [],
    organization_memberships: [],
    organization_requests: [],
});

describe('MembershipsViewModel', () => {
    beforeEach(() => {
        form.organization_id = null;
        form.post.mockClear();
        form.defaults.mockClear();
        success.mockClear();
    });

    it('has no selected organization until one is picked', () => {
        const subject = new MembershipsViewModel(false, pageData());

        expect(subject.selected_primary_organization).toBeNull();
        expect(subject.selected_identifier_label).toBeNull();
    });

    it.each([1, 10, 100])('resolves the primary organization from id %i', (id) => {
        const subject = new MembershipsViewModel(false, pageData());
        form.organization_id = id;

        expect(subject.selected_primary_organization?.id).toBe(1);
        expect(subject.selected_identifier_label).toBe('501st Legion TK Number');
    });

    it('submits the request and shows the new requests on success', () => {
        const event = { preventDefault: vi.fn() } as unknown as Event;
        const subject = new MembershipsViewModel(false, pageData());

        subject.addTrooperRequest(event);

        expect(event.preventDefault).toHaveBeenCalled();
        expect(form.post).toHaveBeenCalledWith('/account.add-trooper-request', expect.any(Object));

        const requests = [{ identifier: 'TK1' }];
        form.post.mock.calls[0][1].onSuccess({
            props: { results: { organization_requests: requests } },
        });

        expect(subject.organization_requests).toEqual(requests);
        expect(form.defaults).toHaveBeenCalled();
        expect(success).toHaveBeenCalled();
    });
});
