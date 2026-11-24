// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type {
    CostumeLeaderboardPageData,
    LeaderboardPageData,
} from '../../../../../resources/svelte/pages/service-records/models/types';

const { page, get } = vi.hoisted(() => ({ page: { props: {} as Record<string, unknown> }, get: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ usePage: () => page, router: { get } }));
vi.mock('ziggy-js', () => ({
    route: (name: string, params?: Record<string, unknown>) =>
        `/${name}?${new URLSearchParams(params as Record<string, string>).toString()}`,
}));

import Leaderboard from '../../../../../resources/svelte/pages/service-records/Leaderboard.svelte';
import Costume from '../../../../../resources/svelte/pages/service-records/Costume.svelte';

const filters = {
    days: null,
    day_options: [30, 60],
    organization_id: null,
    organization: null,
    organizations: [{ value: 3, label: '501st Legion' }],
};

const trooper = {
    id: 1,
    display_name: 'TK-Zeta',
    legal_name: 'Jane Example',
    url: '/service-records/trooper/1',
    troop_count: 12,
};

const costumeStats = {
    id: 7,
    name: 'Stormtrooper',
    total_deployments: '1,200',
    unique_troopers: '3',
    last_deployed: 'Nov 1, 2026',
    top_troopers: [trooper],
};

const data = (overrides: Partial<LeaderboardPageData> = {}): LeaderboardPageData => ({
    filters,
    operatives: [trooper],
    organization_activity: [{ name: '501st Legion', count: 4 }],
    top_costumes: [{ id: 7, name: 'Stormtrooper', count: 9 }],
    costumes: [{ id: 7, name: 'Stormtrooper' }, { id: 8, name: 'Sandtrooper' }],
    costume: null,
    ...overrides,
});

describe('Leaderboard', () => {
    beforeEach(() => get.mockClear());

    it('ranks the troopers with their real names and links', () => {
        page.props = data();

        render(Leaderboard);

        expect(screen.getByRole('link', { name: 'TK-Zeta' }).getAttribute('href')).toBe('/service-records/trooper/1');
        expect(screen.getByText('Jane Example')).toBeTruthy();
        expect(screen.getByText('#1')).toBeTruthy();
    });

    it('offers every period and the clubs', () => {
        page.props = data();

        render(Leaderboard);

        expect(screen.getByRole('link', { name: 'All Time' }).className).toContain('active');
        expect(screen.getByRole('link', { name: '30D' }).getAttribute('href')).toBe('/service-records.leaderboard?days=30');
        expect(screen.getByRole('option', { name: '501st Legion' })).toBeTruthy();
    });

    it('opens a top costume in the panel', async () => {
        page.props = data();

        render(Leaderboard);
        const bar = screen.getByRole('link', { name: /Stormtrooper/ });
        expect(bar.getAttribute('href')).toBe('/service-records.costume?costume=7');
        await fireEvent.click(bar);

        expect(get.mock.calls[0][0]).toBe('/service-records.leaderboard?costume_id=7');
    });

    it('opens a costume found in the lookup', async () => {
        page.props = data();

        render(Leaderboard);
        const box = screen.getByLabelText('Search Costumes');
        await fireEvent.focus(box);
        await fireEvent.input(box, { target: { value: 'sand' } });
        await fireEvent.click(screen.getByRole('button', { name: 'Sandtrooper' }));

        expect(get.mock.calls[0][0]).toBe('/service-records.leaderboard?costume_id=8');
    });

    it('shows the opened costume\'s numbers', () => {
        page.props = data({ costume: costumeStats });

        const { container } = render(Leaderboard);

        const panel = container.querySelector('#costume-stats-panel')!;
        expect(panel.textContent).toContain('1,200');
        expect(panel.textContent).toContain('Top Troopers — Stormtrooper');
    });

    it('says so when nobody trooped', () => {
        page.props = data({ operatives: [], organization_activity: [], top_costumes: [] });

        render(Leaderboard);

        expect(screen.getByText('No troopers found for the selected leaderboard filters.')).toBeTruthy();
        expect(screen.getByText('No costume deployments found.')).toBeTruthy();
    });
});

describe('Costume', () => {
    it('shows the costume\'s rankings and a way back with the same filters', () => {
        page.props = {
            filters: { ...filters, days: 30 },
            costume: costumeStats,
        } satisfies CostumeLeaderboardPageData;

        render(Costume);

        expect(screen.getByRole('heading', { name: 'Stormtrooper' })).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Leaderboard' }).getAttribute('href')).toBe('/service-records.leaderboard?days=30');
        expect(screen.getByRole('link', { name: '60D' }).getAttribute('href')).toBe('/service-records.costume?costume=7&days=60');
    });
});
