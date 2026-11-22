import { describe, expect, it } from 'vitest';
import { SignUpFormViewModel } from '../../../../../resources/svelte/pages/events/models/vms/SignUpFormViewModel.svelte';
import type { SignUpChoices } from '../../../../../resources/svelte/pages/events/models/display-types';

const choices = (overrides: Partial<SignUpChoices & { plans: { value: string; label: string }[] }> = {}) => ({
    organizations: [],
    stations: [],
    requires_costume: true,
    costumes: [{ value: '5', label: 'Clone Trooper' }],
    default_costume: '5',
    plans: [{ value: 'going', label: 'Going' }],
    ...overrides,
});

describe('SignUpFormViewModel', () => {
    it('starts on the costume we can guess and sends it', () => {
        const form = new SignUpFormViewModel(choices());

        expect(form.has_choices).toBe(true);
        expect(form.can_confirm).toBe(true);
        expect(form.payload()).toEqual({ costume_id: '5' });
    });

    it('waits for a costume before Confirm', () => {
        const form = new SignUpFormViewModel(choices({ default_costume: '' }));

        expect(form.can_confirm).toBe(false);

        form.costume_id = 'none';
        expect(form.can_confirm).toBe(true);
    });

    it('signs up straight away when there is nothing to choose', () => {
        const form = new SignUpFormViewModel(choices({ requires_costume: false, costumes: [], default_costume: 'none' }));

        expect(form.has_choices).toBe(false);
        //  no costume picker, so no costume is sent
        expect(form.payload()).toEqual({});
    });

    it('sends the club, station, plans and backup costume that were picked', () => {
        const form = new SignUpFormViewModel(choices({
            organizations: [{ value: 1, label: 'Florida Garrison' }, { value: 2, label: 'Rebel Legion' }],
            stations: [{ value: 9, label: 'Front gate' }],
            plans: [{ value: 'going', label: 'Going' }, { value: 'tentative', label: 'Tentative' }],
        }));

        form.organization_id = '2';
        form.station_id = '9';
        form.status = 'tentative';
        form.backup_costume_id = '5';
        expect(form.payload()).not.toHaveProperty('backup_costume_id');

        form.addBackup();

        expect(form.payload({ trooper_id: 3 })).toEqual({
            trooper_id: 3,
            costume_id: '5',
            organization_id: '2',
            event_shift_station_id: '9',
            status: 'tentative',
            backup_costume_id: '5',
        });
    });

    it('picks a lone club, and a friend\'s first club, by itself', () => {
        const one = new SignUpFormViewModel(choices({ organizations: [{ value: 1, label: 'Florida Garrison' }] }));
        expect(one.only_organization?.label).toBe('Florida Garrison');
        expect(one.organization_id).toBe('1');

        const friend = new SignUpFormViewModel(choices({
            organizations: [{ value: 4, label: 'A' }, { value: 6, label: 'B' }],
        }), true);
        expect(friend.organization_id).toBe('4');
    });

    it('asks for a friend\'s station before Confirm', () => {
        const friend = new SignUpFormViewModel(choices({ stations: [{ value: 9, label: 'Front gate' }] }), true);

        expect(friend.can_confirm).toBe(false);

        friend.station_id = '9';
        expect(friend.can_confirm).toBe(true);
    });
});
