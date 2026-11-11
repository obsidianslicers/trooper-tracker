// @vitest-environment jsdom
import { render, screen } from '@testing-library/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { page } = vi.hoisted(() => ({ page: { props: {} as Record<string, any> } }));

vi.mock('@inertiajs/svelte', () => ({
    usePage: () => page,
    useForm: () => ({
        organization_id: null,
        identifier: null,
        post: vi.fn(),
        defaults: vi.fn(),
        processing: false,
        isDirty: false,
        errors: {},
    }),
    router: { post: vi.fn(), delete: vi.fn() },
}));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import Index from '../../../../../resources/svelte/pages/account/Index.svelte';

const person = { id: 1, legal_name: 'Ben Kenobi', display_name: 'Obi-Wan' };

const props = (overrides: Record<string, unknown> = {}) => ({
    trooper_id: 1,
    is_visitor: false,
    is_handler: false,
    deletion_requested_at: null,
    visitor_expires_at: null,
    email: 'trooper@example.com',
    plugin_slots: {},
    details: {
        legal_name: 'Anakin Skywalker',
        display_name: 'Vader',
        phone: '',
        display_costume_id: null,
        theme: 'stormtrooper',
        display_costumes: [],
        theme_enums: [],
    },
    notifications: {
        notification_frequency: 'never',
        push_notifications_enabled: false,
        notification_frequency_enums: [],
        organization_notifications: [],
        trooper_notification_enums: [],
        administrative_notification_enums: [],
        notification_preferences: {},
    },
    memberships: {
        organizations: [],
        organization_options: [],
        organization_memberships: [],
        organization_requests: [],
    },
    costumes: [],
    friends: [],
    minors: [],
    ...overrides,
});

describe('Account Index', () => {
    beforeEach(() => (page.props = props()));

    it('shows the standard account tabs', () => {
        render(Index);

        for (const name of ['Profile', 'Notifications', 'Costumes', 'Memberships', 'Delete Account']) {
            expect(screen.getAllByText(name).length).toBeGreaterThan(0);
        }
    });

    it('hides the costumes tab from handlers', () => {
        page.props = props({ is_handler: true });

        render(Index);

        expect(screen.queryByText('Costumes')).toBeNull();
    });

    it('only shows friends and cadet tabs when there are some', () => {
        const { unmount } = render(Index);
        expect(screen.queryByText('Friends')).toBeNull();
        expect(screen.queryByText('Cadets')).toBeNull();
        unmount();

        page.props = props({ friends: [person], minors: [person] });
        render(Index);

        expect(screen.getAllByText('Friends').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Cadets').length).toBeGreaterThan(0);
    });

    it('warns about a scheduled deletion instead of offering to delete', () => {
        page.props = props({ deletion_requested_at: '2026-01-01T12:00:00' });

        render(Index);

        expect(screen.getByText('Account deletion scheduled.')).toBeTruthy();
        expect(screen.getByText('Jan 31, 2026')).toBeTruthy();
        expect(screen.queryByText('Delete Account')).toBeNull();
    });

    it('tells expiring visitors when their access ends', () => {
        page.props = props({ is_visitor: true, visitor_expires_at: '2026-03-05T09:30:00' });

        render(Index);

        expect(screen.getByText(/Your visitor status expires/)).toBeTruthy();
    });
});
