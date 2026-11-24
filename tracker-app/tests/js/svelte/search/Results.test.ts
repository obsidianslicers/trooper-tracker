// @vitest-environment jsdom
import { render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/svelte', () => ({ router: { post: vi.fn() }, page: { props: {} } }));
vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, string> = {}) => `/${name}?${new URLSearchParams(params)}`,
}));

import Results from '../../../../resources/svelte/pages/search/Results.svelte';
import type { SearchEvent, SearchResultsPageData, SearchTrooper } from '../../../../resources/svelte/pages/search/models/types';

const trooper = (id: number): SearchTrooper => ({
    id,
    display_name: `TK-${id}`,
    legal_name: 'Ben Smith',
    url: `/sr/${id}`,
    identifiers: [{ club: '501st Legion', label: `TKID: ${id}` }],
});

const event: SearchEvent = {
    id: 9, name: 'Parade', url: '/events/9', venue_address: '1 Main St', organization: null, date: 'Jan 5, 2026', status: 'Open',
};

const props = (overrides: Partial<SearchResultsPageData> = {}): SearchResultsPageData => ({
    term: 'tk',
    type: 'all',
    searched: true,
    troopers: [trooper(421)],
    events: [event],
    limit: 25,
    search_title: 'Holonet Scanner',
    search_types: { all: 'All', troopers: 'Troopers', events: 'Events' },
    ...overrides,
});

describe('search results', () => {
    it('asks for a longer term before searching', () => {
        render(Results, { props: props({ term: 'a', searched: false, troopers: null, events: null }) });

        expect(screen.getByText('Enter at least 2 characters to search.')).toBeTruthy();
        expect(screen.queryByText('Troopers', { selector: '.card-header' })).toBeNull();
    });

    it('lists troopers with their club ids and events with their details', () => {
        render(Results, { props: props() });

        expect(screen.getByText('TK-421').getAttribute('href')).toBe('/sr/421');
        expect(screen.getByText('Ben Smith')).toBeTruthy();
        expect(screen.getByText('TKID: 421').getAttribute('title')).toBe('501st Legion');
        expect(screen.getByText('Parade').getAttribute('href')).toBe('/events/9');
        expect(screen.getByText('1 Main St')).toBeTruthy();
        expect(screen.getByText('—')).toBeTruthy();
        expect(screen.queryByText(/Showing first 25 results/)).toBeNull();
    });

    it('says when a kind found nothing, and leaves out a kind not searched', () => {
        render(Results, { props: props({ type: 'troopers', troopers: [], events: null }) });

        expect(screen.getByText('No troopers found for “tk”.')).toBeTruthy();
        expect(screen.queryByText('Events / Troops')).toBeNull();
    });

    it('links to a narrower search when the limit was hit', () => {
        render(Results, { props: props({ troopers: Array.from({ length: 25 }, (_, i) => trooper(i + 1)) }) });

        const link = screen.getByText('search troopers only');
        expect(link.getAttribute('href')).toContain('type=troopers');
        expect(link.getAttribute('href')).toContain('q=tk');
    });

    it('keeps the term and kind in the search form', () => {
        render(Results, { props: props({ type: 'events' }) });

        expect((screen.getByRole('textbox') as HTMLInputElement).value).toBe('tk');
        expect((screen.getByRole('combobox') as HTMLSelectElement).value).toBe('events');
    });
});
