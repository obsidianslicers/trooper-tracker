import { describe, expect, it } from 'vitest';
import eventSelector from '../../../resources/js/events/eventSelector.js';

function selector(selectedIds) {
    const subject = eventSelector();
    subject.hosting_organization_all_ids = ['1', '2', '3', '4', '5'];
    subject.hosting_organization_parents = { 2: '1', 3: '2', 4: '2', 5: '1' };
    subject.form.hosting_organization_ids = selectedIds;

    return subject;
}

describe('eventSelector host matching', () => {
    it('picking a squad also matches its garrison and club', () => {
        const ids = selector(['3']).getMatchingHostingOrganizationIds();

        expect([...ids].sort()).toEqual(['1', '2', '3']);
    });

    it('picking a garrison also matches every squad beneath it', () => {
        const ids = selector(['2']).getMatchingHostingOrganizationIds();

        expect([...ids].sort()).toEqual(['1', '2', '3', '4']);
    });
});
