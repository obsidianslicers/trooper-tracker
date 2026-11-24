// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { UnreadNotice } from '../../../../../resources/svelte/lib/domains/types.svelte';
import type { PushNotificationsPageData } from '../../../../../resources/svelte/pages/account/models/types';

const { page, post, del } = vi.hoisted(() => ({
    page: { props: {} as Record<string, unknown> },
    post: vi.fn(),
    del: vi.fn(),
}));

vi.mock('@inertiajs/svelte', () => ({ usePage: () => page, router: { post, delete: del } }));
vi.mock('ziggy-js', () => ({
    route: (name: string, params?: Record<string, unknown>) =>
        `/${name}${params ? '/' + Object.values(params).join('/') : ''}`,
}));

import Notices from '../../../../../resources/svelte/pages/account/Notices.svelte';
import PushNotifications from '../../../../../resources/svelte/pages/account/PushNotifications.svelte';

const notice: UnreadNotice = {
    id: 5,
    type: 'warning',
    heading: 'Warning',
    title: 'Costume check moved',
    html: '<p>Now on <strong>Friday</strong></p>',
};

describe('Notices', () => {
    beforeEach(() => post.mockClear());

    it('shows each unread notice with its message', () => {
        page.props = { notices: [notice] };

        render(Notices);

        expect(screen.getByText('Costume check moved')).toBeTruthy();
        expect(screen.getByText('Friday').tagName).toBe('STRONG');
    });

    it('takes a dismissed notice away and marks it read', async () => {
        page.props = { notices: [notice] };

        render(Notices);
        await fireEvent.click(screen.getByRole('button', { name: 'Mark as read' }));

        expect(screen.queryByText('Costume check moved')).toBeNull();
        expect(screen.getByText('You have no unread notices.')).toBeTruthy();
        expect(post).toHaveBeenCalledWith('/account.update-notice-as-read/5', {}, expect.anything());
    });
});

describe('PushNotifications', () => {
    const data = (overrides: Partial<PushNotificationsPageData> = {}): PushNotificationsPageData => ({
        notifications: [
            { id: 'a', title: 'Holiday Parade is open', body: 'Sign up now', is_unread: true, sent_ago: '1 hour ago' },
        ],
        troops_to_confirm: [
            { id: 9, event_name: 'Spring Fling', shift_time: '10 AM', url: '/events/details/3#shift-4' },
        ],
        ...overrides,
    });

    beforeEach(() => {
        post.mockClear();
        del.mockClear();
    });

    it('asks about troops first, then lists the notifications', () => {
        page.props = data();

        const { container } = render(PushNotifications);

        const text = container.textContent ?? '';
        expect(text.indexOf('Did you make it?')).toBeLessThan(text.indexOf('Holiday Parade is open'));
        expect(screen.getByRole('link', { name: 'Spring Fling' }).getAttribute('href')).toBe('/events/details/3#shift-4');
        expect(container.querySelector('.notification-unread')).toBeTruthy();
    });

    it('answers a troop, opens a notification and clears them all', async () => {
        page.props = data();

        render(PushNotifications);
        await fireEvent.click(screen.getByRole('button', { name: 'Missed It' }));
        await fireEvent.click(screen.getByRole('button', { name: /Holiday Parade is open/ }));
        await fireEvent.click(screen.getByRole('button', { name: 'Clear all' }));

        expect(post).toHaveBeenCalledWith('/events.confirm-attendance/9', { status: 'unabletoattend' }, expect.anything());
        expect(post).toHaveBeenCalledWith('/account.push-notifications.read/a');
        expect(del).toHaveBeenCalled();
    });

    it('says so when there is nothing', () => {
        page.props = data({ notifications: [], troops_to_confirm: [] });

        render(PushNotifications);

        expect(screen.getByText('No notifications yet.')).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Clear all' })).toBeNull();
    });
});
