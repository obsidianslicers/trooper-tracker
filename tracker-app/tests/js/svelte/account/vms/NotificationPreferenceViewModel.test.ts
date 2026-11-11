import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post, success } = vi.hoisted(() => ({ post: vi.fn(), success: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { post }, useForm: vi.fn() }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));
vi.mock('../../../../../resources/svelte/lib/states/toast-state.svelte', () => ({
    default: { success },
}));

import { AdministrativeNotificationsViewModel } from '../../../../../resources/svelte/pages/account/models/vms/AdministrativeNotificationsViewModel.svelte';
import { NotificationsViewModel } from '../../../../../resources/svelte/pages/account/models/vms/NotificationsViewModel.svelte';
import { TrooperNotificationsViewModel } from '../../../../../resources/svelte/pages/account/models/vms/TrooperNotificationsViewModel.svelte';

const enums = [
    { value: 'event_created', label: 'Event Created' },
    { value: 'event_cancelled', label: 'Event Cancelled' },
];

describe('NotificationPreferenceViewModel', () => {
    beforeEach(() => {
        post.mockClear();
        success.mockClear();
    });

    it('enables every channel that has no saved preference', () => {
        const subject = new TrooperNotificationsViewModel({
            trooper_notification_enums: enums,
            notification_preferences: { event_created: { mail: false } },
        });

        expect(subject.notification_preferences.event_created).toEqual({
            mail: false,
            fcm: true,
            database: true,
        });
        expect(subject.notification_preferences.event_cancelled).toEqual({
            mail: true,
            fcm: true,
            database: true,
        });
    });

    it('copes with missing enums and preferences', () => {
        const subject = new TrooperNotificationsViewModel({
            trooper_notification_enums: null as any,
            notification_preferences: null as any,
        });

        expect(subject.notification_enums).toEqual([]);
        expect(subject.notification_preferences).toEqual({});
    });

    it.each([
        ['updateMailNotificationPreference', 'mail'],
        ['updateFcmNotificationPreference', 'fcm'],
        ['updateDatabaseNotificationPreference', 'database'],
    ])('%s saves the %s channel', (method, channel) => {
        const subject: any = new TrooperNotificationsViewModel({
            trooper_notification_enums: enums,
            notification_preferences: {},
        });
        subject.notification_preferences.event_created[channel] = false;

        subject[method]('event_created');

        expect(post).toHaveBeenCalledWith(
            '/account.update-notification-preference',
            { notification: 'event_created', channel, enabled: false },
            expect.any(Object),
        );
    });

    it('confirms trooper preference updates', () => {
        const subject = new TrooperNotificationsViewModel({
            trooper_notification_enums: enums,
            notification_preferences: {},
        });

        subject.updateMailNotificationPreference('event_created');
        post.mock.calls[0][2].onSuccess({});

        expect(success).toHaveBeenCalledWith('Trooper Notification Preference updated successfully.');
    });

    it('confirms administrative preference updates', () => {
        const subject = new AdministrativeNotificationsViewModel({
            administrative_notification_enums: enums,
            notification_preferences: {},
        });

        subject.updateMailNotificationPreference('event_created');
        post.mock.calls[0][2].onSuccess({});

        expect(success).toHaveBeenCalledWith(
            'Administrative Notification Preference updated successfully.',
        );
    });
});

describe('NotificationsViewModel', () => {
    it('keeps the page data it was given', () => {
        const page_data = { notification_frequency: 'daily' } as any;

        const subject = new NotificationsViewModel(page_data);

        expect(subject.page_data).toBe(page_data);
    });

    it('falls back to empty page data', () => {
        const subject = new NotificationsViewModel(null as any);

        expect(subject.page_data).toEqual({});
    });
});
