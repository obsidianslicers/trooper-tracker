import { beforeEach, describe, expect, it, vi } from 'vitest';

const { get } = vi.hoisted(() => ({ get: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { get } }));
vi.mock('ziggy-js', () => ({
    route: (name: string, params?: Record<string, unknown>) =>
        `/${name}?${new URLSearchParams(params as Record<string, string>).toString()}`,
}));

import {
    CostumeSearchViewModel,
    LeaderboardFiltersViewModel,
    LeaderboardViewModel,
} from '../../../../../resources/svelte/pages/service-records/models/vms';
import type { LeaderboardFilters } from '../../../../../resources/svelte/pages/service-records/models/types';

const filters = (overrides: Partial<LeaderboardFilters> = {}): LeaderboardFilters => ({
    days: 90,
    day_options: [30, 60, 90, 180, 360],
    organization_id: 3,
    organization: { name: '501st Legion', logo_url: '/logo.png' },
    organizations: [{ value: 3, label: '501st Legion' }],
    ...overrides,
});

describe('LeaderboardFiltersViewModel', () => {
    beforeEach(() => get.mockClear());

    it('builds period links that keep the club', () => {
        const subject = new LeaderboardFiltersViewModel(filters(), 'service-records.leaderboard');

        expect(subject.url(30)).toBe('/service-records.leaderboard?days=30&organization_id=3');
        expect(subject.url(null)).toBe('/service-records.leaderboard?organization_id=3');
    });

    it('keeps the costume on its own page', () => {
        const subject = new LeaderboardFiltersViewModel(
            filters({ organization_id: null }),
            'service-records.costume',
            { costume: 7 },
        );

        expect(subject.url(60)).toBe('/service-records.costume?costume=7&days=60');
        expect(subject.organizationId).toBe('');
    });

    it('picks a club and keeps the period', () => {
        const subject = new LeaderboardFiltersViewModel(filters(), 'service-records.leaderboard');

        subject.pickClub('');
        subject.pickClub('5');

        expect(get).toHaveBeenNthCalledWith(1, '/service-records.leaderboard?days=90');
        expect(get).toHaveBeenNthCalledWith(2, '/service-records.leaderboard?days=90&organization_id=5');
    });
});

describe('LeaderboardViewModel', () => {
    beforeEach(() => get.mockClear());

    it('reloads only the costume panel, with the same filters', () => {
        const onloaded = vi.fn();
        const subject = new LeaderboardViewModel(filters());

        subject.openCostume(7, onloaded);

        expect(subject.loading).toBe(true);
        const [url, data, options] = get.mock.calls[0];
        expect(url).toBe('/service-records.leaderboard?costume_id=7&days=90&organization_id=3');
        expect(data).toEqual({});
        expect(options).toMatchObject({ only: ['costume'], preserveState: true, preserveScroll: true });

        options.onSuccess();
        options.onFinish();
        expect(onloaded).toHaveBeenCalled();
        expect(subject.loading).toBe(false);
    });
});

describe('CostumeSearchViewModel', () => {
    const costumes = [
        { id: 1, name: 'Stormtrooper' },
        { id: 2, name: 'Sandtrooper' },
        { id: 3, name: 'Boba Fett' },
    ];

    it('matches once two letters are typed', () => {
        const subject = new CostumeSearchViewModel(costumes);

        subject.search = 't';
        expect(subject.matches).toEqual([]);

        subject.search = 'TROOP';
        expect(subject.matches.map((c) => c.id)).toEqual([1, 2]);
    });

    it('fills the box with the chosen costume and closes the list', () => {
        const subject = new CostumeSearchViewModel(costumes);
        subject.showResults = true;

        subject.choose(costumes[2]);

        expect(subject.search).toBe('Boba Fett');
        expect(subject.showResults).toBe(false);
    });
});
