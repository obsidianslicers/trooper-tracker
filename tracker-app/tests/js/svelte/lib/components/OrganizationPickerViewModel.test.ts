import { describe, expect, it } from 'vitest';
import {
    OrganizationPickerViewModel,
    organizationName,
} from '../../../../../resources/svelte/lib/components/models/OrganizationPickerViewModel.svelte';

const options = [
    { value: null, label: 'Everyone' },
    { value: 1, label: 'Florida Garrison' },
    { value: 2, label: '— Tampa Squad' },
    { value: 3, label: '— — Bay Detachment' },
];

describe('OrganizationPickerViewModel', () => {
    it('strips the level indent from a name', () => {
        expect(organizationName(options[3])).toBe('Bay Detachment');
    });

    it('lists every organization until a search is typed', () => {
        const vm = new OrganizationPickerViewModel(() => options);

        expect(vm.filtered).toHaveLength(4);
    });

    it('searches by name, ignoring case and indent', () => {
        const vm = new OrganizationPickerViewModel(() => options);

        vm.term = 'tampa';

        expect(vm.filtered.map((o) => o.value)).toEqual([2]);
    });

    it('shows the picked organization by name, including Everyone', () => {
        const vm = new OrganizationPickerViewModel(() => options);

        expect(vm.selectedLabel(2)).toBe('Tampa Squad');
        expect(vm.selectedLabel(null)).toBe('Everyone');
        expect(vm.selectedLabel(99)).toBeNull();
    });

    it('opens with an empty search', () => {
        const vm = new OrganizationPickerViewModel(() => options);
        vm.term = 'old';

        vm.open();

        expect(vm.show_modal).toBe(true);
        expect(vm.term).toBe('');
    });
});
