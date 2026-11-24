// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/svelte', () => ({ router: { post: vi.fn() }, page: { props: {} } }));
vi.mock('bootstrap', () => ({
    Modal: class {
        show() {}
        hide() {}
        dispose() {}
    },
}));

import Achievements from '../../../../../resources/svelte/pages/service-records/Achievements.svelte';
import Awards from '../../../../../resources/svelte/pages/service-records/Awards.svelte';
import CommandStaff from '../../../../../resources/svelte/pages/service-records/CommandStaff.svelte';
import PhotoGallery from '../../../../../resources/svelte/pages/service-records/PhotoGallery.svelte';
import type {
    AchievementsPageData,
    AwardsPageData,
    CommandStaffPageData,
    PhotoGalleryPageData,
} from '../../../../../resources/svelte/pages/service-records/models/list-types';

describe('Achievements', () => {
    it('lists who reached what, and marks the period picked', () => {
        const props: AchievementsPageData = {
            days: 60,
            achievements: [{ id: 1, trooper_name: 'TK-421', trooper_url: '/sr/1', description: '10 troops', icon: 'fa-star', date: 'Jan 5, 2026' }],
        };
        render(Achievements, props);

        expect(screen.getAllByText('TK-421')[0].closest('a')?.getAttribute('href')).toBe('/sr/1');
        expect(screen.getAllByText('10 troops').length).toBeGreaterThan(0);
        expect(screen.getByText('60D').classList.contains('active')).toBe(true);
        expect(screen.getByText('30D').getAttribute('href')).toContain('days=30');
    });

    it('says when nothing was reached in the period', () => {
        render(Achievements, { days: 30, achievements: [] });

        expect(screen.getByText('No achievements found in the last 30 days.')).toBeTruthy();
    });
});

describe('Awards', () => {
    it('lists the award, club and trooper', () => {
        const props: AwardsPageData = {
            days: 30,
            awards: [{ id: 1, award: 'Trooper of the Year', organization: '501st Legion', logo_url: '/logo.png', trooper_name: 'TK-421', trooper_url: '/sr/1', date: 'Jan 5, 2026' }],
        };
        render(Awards, props);

        expect(screen.getAllByText('Trooper of the Year').length).toBeGreaterThan(0);
        expect(screen.getAllByText('501st Legion').length).toBeGreaterThan(0);
    });

    it('says when nothing was awarded in the period', () => {
        render(Awards, { days: 90, awards: [] });

        expect(screen.getByText('No awards found in the last 90 days.')).toBeTruthy();
    });
});

describe('CommandStaff', () => {
    it('lists each member with their role and clubs, or a dash', () => {
        const props: CommandStaffPageData = {
            troopers: [
                { id: 1, display_name: 'Alpha', url: '/sr/1', role: 'Moderator', organizations: '501st Legion' },
                { id: 2, display_name: 'Bravo', url: '/sr/2', role: 'System Owner', organizations: '' },
            ],
        };
        render(CommandStaff, props);

        expect(screen.getAllByText('501st Legion').length).toBeGreaterThan(0);
        expect(screen.getByText('—')).toBeTruthy();
        expect(screen.getByText('None listed')).toBeTruthy();
    });
});

describe('PhotoGallery', () => {
    const props = (data: PhotoGalleryPageData['photos']['data']): PhotoGalleryPageData => ({
        photos: { data, meta: { links: [] } },
    });

    it('says when there are no photos', () => {
        render(PhotoGallery, props([]));

        expect(screen.getByText('No event photos have been uploaded yet.')).toBeTruthy();
    });

    it('shows each photo with its event, uploader and tags, and opens it large', async () => {
        render(PhotoGallery, props([{
            id: 1, small_url: '/s.jpg', large_url: '/l.jpg', event_name: 'Parade', event_url: '/events/1',
            event_date: 'Jan 1, 2026', uploaded_by: 'TK-421', tagged: 'Alpha, Bravo',
        }]));

        expect(screen.getByText('Parade').getAttribute('href')).toBe('/events/1');
        expect(screen.getByText('By TK-421')).toBeTruthy();
        expect(screen.getByText('Alpha, Bravo')).toBeTruthy();

        await fireEvent.click(document.querySelector('img[src="/s.jpg"]')!.closest('a')!);
        await tick();

        expect(document.querySelector('img[src="/l.jpg"]')).toBeTruthy();
    });
});
