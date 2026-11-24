import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post, del } = vi.hoisted(() => ({ post: vi.fn(), del: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { post, delete: del } }));
vi.mock('ziggy-js', () => ({
    route: (name: string, params?: Record<string, unknown>) =>
        `/${name}${params ? '/' + Object.values(params).join('/') : ''}`,
}));

import { PushNotificationsViewModel } from '../../../../../resources/svelte/pages/account/models/vms/PushNotificationsViewModel.svelte';
import type {
    PushNotification,
    PushNotificationsPageData,
    TroopToConfirm,
} from '../../../../../resources/svelte/pages/account/models/types';

const notification: PushNotification = {
    id: 'abc',
    title: 'Holiday Parade is open',
    body: 'Sign up now',
    is_unread: true,
    sent_ago: '2 hours ago',
};

const troop: TroopToConfirm = {
    id: 9,
    event_name: 'Spring Fling',
    shift_time: '10:00 AM - 2:00 PM',
    url: '/events/details/3#shift-4',
};

const pageData = (overrides: Partial<PushNotificationsPageData> = {}): PushNotificationsPageData => ({
    notifications: [notification],
    troops_to_confirm: [troop],
    ...overrides,
});

describe('PushNotificationsViewModel', () => {
    beforeEach(() => {
        post.mockClear();
        del.mockClear();
    });

    it('is empty only with no notifications and no troops to confirm', () => {
        expect(new PushNotificationsViewModel(pageData()).isEmpty).toBe(false);
        expect(new PushNotificationsViewModel(pageData({ notifications: [] })).isEmpty).toBe(false);
        expect(new PushNotificationsViewModel(pageData({ troops_to_confirm: [] })).isEmpty).toBe(false);
        expect(
            new PushNotificationsViewModel(pageData({ notifications: [], troops_to_confirm: [] })).isEmpty,
        ).toBe(true);
    });

    it('opens a notification by marking it read', () => {
        new PushNotificationsViewModel(pageData()).open(notification);

        expect(post).toHaveBeenCalledWith('/account.push-notifications.read/abc');
    });

    it('clears all notifications', () => {
        new PushNotificationsViewModel(pageData()).clearAll();

        expect(del).toHaveBeenCalledWith('/account.push-notifications.clear', { preserveScroll: true });
    });

    it('answers whether the trooper made it', () => {
        new PushNotificationsViewModel(pageData()).answer(troop, 'unabletoattend');

        expect(post).toHaveBeenCalledWith(
            '/events.confirm-attendance/9',
            { status: 'unabletoattend' },
            { preserveScroll: true },
        );
    });
});
