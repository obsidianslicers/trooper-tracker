// @vitest-environment jsdom
import { render, screen } from '@testing-library/svelte';
import { describe, expect, it } from 'vitest';
import SignUpChoices from '../../../../../resources/svelte/pages/events/components/display/SignUpChoices.svelte';
import { SignUpFormViewModel } from '../../../../../resources/svelte/pages/events/models/vms/SignUpFormViewModel.svelte';

const form = (requires_costume: boolean) => new SignUpFormViewModel({
    organizations: [],
    stations: [],
    requires_costume,
    costumes: requires_costume ? [{ value: '5', label: 'Clone Trooper' }] : [],
    default_costume: requires_costume ? '5' : 'none',
    plans: [{ value: 'going', label: 'Going' }, { value: 'tentative', label: 'Tentative' }],
});

describe('SignUpChoices', () => {
    it('says no costume is needed where the picker would be', () => {
        render(SignUpChoices, { form: form(false), key: 'shift-1' });

        expect(screen.getByText('No costume needed for this event')).toBeTruthy();
        expect(screen.queryByLabelText('Costume')).toBeNull();
    });

    it('offers the costume picker when the event needs one', () => {
        render(SignUpChoices, { form: form(true), key: 'shift-1' });

        expect(screen.getByLabelText('Costume')).toBeTruthy();
        expect(screen.queryByText('No costume needed for this event')).toBeNull();
    });
});
