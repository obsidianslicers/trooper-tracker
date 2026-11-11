// @vitest-environment jsdom
import { render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';

vi.mock('ziggy-js', () => ({ route: (name: string, params: { trooper: number }) => `/${name}/${params.trooper}` }));

import Friends from '../../../../../resources/svelte/pages/account/components/Friends.svelte';
import Minors from '../../../../../resources/svelte/pages/account/components/Minors.svelte';

const trooper = { id: 7, legal_name: 'Ben Kenobi', display_name: 'Obi-Wan' };

describe.each([
    ['Friends', Friends, 'friends', 'No friend accounts are currently linked to you.'],
    ['Minors', Minors, 'minors', 'No cadet accounts are currently linked to you.'],
])('%s', (_name, Component, property, empty_message) => {
    it('tells the trooper when nobody is linked', () => {
        render(Component as any, { [property]: [] });

        expect(screen.getByText(empty_message)).toBeTruthy();
    });

    it('lists linked troopers with a link to their service record', () => {
        render(Component as any, { [property]: [trooper] });

        const link = screen.getByRole('link', { name: 'Ben Kenobi' });

        expect(link.getAttribute('href')).toBe('/service-records.trooper/7');
        expect(screen.getByText('Obi-Wan')).toBeTruthy();
        expect(screen.queryByText(empty_message)).toBeNull();
    });
});
