// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post, success } = vi.hoisted(() => ({ post: vi.fn(), success: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { post }, useForm: vi.fn() }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));
vi.mock('../../../../../resources/svelte/lib/states/toast-state.svelte', () => ({
    default: { success },
}));

import AdministrativeNotifications from '../../../../../resources/svelte/pages/account/components/AdministrativeNotifications.svelte';
import EventNotifications from '../../../../../resources/svelte/pages/account/components/EventNotifications.svelte';
import NotificationPreferences from '../../../../../resources/svelte/pages/account/components/NotificationPreferences.svelte';
import Notifications from '../../../../../resources/svelte/pages/account/components/Notifications.svelte';
import TrooperNotifications from '../../../../../resources/svelte/pages/account/components/TrooperNotifications.svelte';
import { TrooperNotificationsViewModel } from '../../../../../resources/svelte/pages/account/models/vms/TrooperNotificationsViewModel.svelte';

const enums = [{ value: 'event_created', label: 'Event Created' }];

const eventNotifications = () => ({
    notification_frequency: 'daily',
    push_notifications_enabled: false,
    notification_frequency_enums: [
        { value: 'daily', label: 'Daily' },
        { value: 'instant', label: 'Instant' },
    ],
    organization_notifications: [
        {
            id: 1,
            name: '501st Legion',
            enabled: false,
            regions: [
                {
                    id: 10,
                    name: 'Garrison',
                    enabled: false,
                    units: [{ id: 100, name: 'Squad A', enabled: false }],
                },
            ],
        },
    ],
});

const notifications = (administrative: unknown[] = []) => ({
    ...eventNotifications(),
    trooper_notification_enums: enums,
    administrative_notification_enums: administrative,
    notification_preferences: {},
});

describe('NotificationPreferences', () => {
    beforeEach(() => post.mockClear());

    it('lists each notification type with its channels', () => {
        const vm = new TrooperNotificationsViewModel({
            trooper_notification_enums: enums,
            notification_preferences: {},
        });

        render(NotificationPreferences, { vm });

        expect(screen.getByText('Event Created')).toBeTruthy();
        expect(screen.getAllByRole('checkbox')).toHaveLength(3);
    });

    it('saves a channel when the trooper turns it off', async () => {
        const vm = new TrooperNotificationsViewModel({
            trooper_notification_enums: enums,
            notification_preferences: {},
        });
        render(NotificationPreferences, { vm });

        await fireEvent.click(screen.getAllByRole('checkbox')[0]);

        expect(post).toHaveBeenCalledWith(
            '/account.update-notification-preference',
            { notification: 'event_created', channel: 'mail', enabled: false },
            expect.any(Object),
        );
    });
});

describe('EventNotifications', () => {
    beforeEach(() => post.mockClear());

    it('shows the organization hierarchy', () => {
        render(EventNotifications, { notifications: eventNotifications() as any });

        expect(screen.getByLabelText('501st Legion')).toBeTruthy();
        expect(screen.getByLabelText('Garrison')).toBeTruthy();
        expect(screen.getByLabelText('Squad A')).toBeTruthy();
    });

    it('subscribes to an organization and everything beneath it', async () => {
        render(EventNotifications, { notifications: eventNotifications() as any });

        await fireEvent.click(screen.getByLabelText('501st Legion'));

        expect((screen.getByLabelText('Garrison') as HTMLInputElement).checked).toBe(true);
        expect((screen.getByLabelText('Squad A') as HTMLInputElement).checked).toBe(true);
        expect(post).toHaveBeenCalledWith(
            '/account.update-organization-notifications',
            { organization_ids: [1, 100], enabled: true },
            expect.any(Object),
        );
    });

    it('saves a changed notification frequency', async () => {
        render(EventNotifications, { notifications: eventNotifications() as any });

        await fireEvent.change(screen.getByLabelText('Notification Frequency'), {
            target: { value: 'instant' },
        });

        expect(post).toHaveBeenCalledWith(
            '/account.update-notification-frequency',
            { notification_frequency: 'instant' },
            expect.any(Object),
        );
    });
});

describe('Notifications', () => {
    it('shows event and preference sections', () => {
        render(Notifications, { notifications: notifications() as any });

        expect(screen.getByText('Organization Event Notifications')).toBeTruthy();
        expect(screen.getByText('Notification Preferences')).toBeTruthy();
    });

    it('hides administrative preferences when there are none', () => {
        render(Notifications, { notifications: notifications() as any });

        expect(screen.queryByText('Administrative Preferences')).toBeNull();
    });

    it('shows administrative preferences when the trooper has them', () => {
        render(Notifications, { notifications: notifications(enums) as any });

        expect(screen.getByText('Administrative Preferences')).toBeTruthy();
    });
});

describe('TrooperNotifications and AdministrativeNotifications', () => {
    it('explain the trooper channels', () => {
        render(TrooperNotifications, { notifications: notifications() as any });

        expect(screen.getByText(/Control which channels you receive notifications on/)).toBeTruthy();
    });

    it('explain the administrative channels', () => {
        render(AdministrativeNotifications, { notifications: notifications(enums) as any });

        expect(screen.getByText(/receive administrative notifications on/)).toBeTruthy();
    });
});
