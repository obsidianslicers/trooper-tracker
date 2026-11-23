// @vitest-environment jsdom
import { render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/svelte', () => ({ router: { post: vi.fn() } }));

import RowEditor from '../../../../../resources/svelte/pages/events/components/display/RowEditor.svelte';
import type { RowEditor as Editor, TrooperRow } from '../../../../../resources/svelte/pages/events/models/display-types';
import { EventDisplayViewModel } from '../../../../../resources/svelte/pages/events/models/vms/EventDisplayViewModel.svelte';

const editor = (overrides: Partial<Editor> = {}): Editor => ({
    stations: null,
    organizations: null,
    costumes: null,
    requires_costume: true,
    plans: { value: 'going', options: [{ value: 'going', label: 'Going' }, { value: 'tentative', label: 'Tentative' }] },
    can_cancel: true,
    cancel_confirm: 'Cancel your sign-up for this shift?',
    ...overrides,
});

const row = { id: 7, is_mine: true, actions: { update_url: '/events/update/7/trooper' } } as unknown as TrooperRow;

const show = (overrides: Partial<Editor>) => render(RowEditor, {
    vm: new EventDisplayViewModel([]),
    row,
    editor: editor(overrides),
    shift_id: 1,
    has_picker_beside: false,
});

describe('RowEditor', () => {
    it('says no costume is needed when the event needs none', () => {
        show({ requires_costume: false });

        expect(screen.getByText('No costume needed for this event')).toBeTruthy();
    });

    it('says nothing about costumes when the viewer just may not change one', () => {
        show({ requires_costume: true, costumes: null });

        expect(screen.queryByText('No costume needed for this event')).toBeNull();
    });

    it('offers the costume picker when there is one', () => {
        show({ costumes: { value: '5', backup_value: '', options: [{ value: '5', label: 'Clone Trooper' }] } });

        expect(screen.getByLabelText('Costume')).toBeTruthy();
        expect(screen.queryByText('No costume needed for this event')).toBeNull();
    });
});
