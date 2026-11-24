// @vitest-environment jsdom
import { afterEach, describe, expect, it } from 'vitest';

import type { RecordTrooper } from '../../../../../resources/svelte/pages/service-records/models/record-types';
import { ServiceRecordViewModel } from '../../../../../resources/svelte/pages/service-records/models/vms/ServiceRecordViewModel.svelte';

const trooper = (is_departed = false): RecordTrooper => ({
    id: 1,
    display_name: 'TK-421',
    rank_title: 'Recruit',
    rank_theme: 'secondary',
    is_departed,
    is_handler: false,
    is_self: true,
    edit_url: null,
    missing_credit_url: null,
});

describe('ServiceRecordViewModel', () => {
    afterEach(() => localStorage.clear());

    it('opens on Upcoming, or on History for a trooper who has departed', () => {
        expect(new ServiceRecordViewModel(trooper()).tab).toBe('upcoming');
        expect(new ServiceRecordViewModel(trooper(true)).tab).toBe('history');
    });

    it('shows cards on phones and a table on wider screens until the trooper picks', () => {
        expect(new ServiceRecordViewModel(trooper(), 400).layout).toBe('cards');
        expect(new ServiceRecordViewModel(trooper(), 1280).layout).toBe('table');
    });

    it('remembers the picked layout', () => {
        const subject = new ServiceRecordViewModel(trooper(), 1280);

        subject.toggleLayout();

        expect(subject.layout).toBe('cards');
        expect(localStorage.getItem('upcomingShiftsView')).toBe('cards');
        expect(new ServiceRecordViewModel(trooper(), 1280).layout).toBe('cards');
    });
});
