import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post, success } = vi.hoisted(() => ({ post: vi.fn(), success: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { post }, useForm: vi.fn() }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));
vi.mock('../../../../../resources/svelte/lib/states/toast-state.svelte', () => ({
    default: { success },
}));

import {
    EventNotificationsViewModel,
    type EventNotificationsPageData,
} from '../../../../../resources/svelte/pages/account/models/vms/EventNotificationsViewModel.svelte';

const pageData = (): EventNotificationsPageData => ({
    notification_frequency: 'daily',
    push_notifications_enabled: true,
    notification_frequency_enums: [{ value: 'daily', label: 'Daily' }],
    organization_notifications: [
        {
            id: 1,
            name: '501st Legion',
            enabled: true,
            regions: [
                {
                    id: 10,
                    name: 'Garrison',
                    enabled: true,
                    units: [
                        { id: 100, name: 'Squad A', enabled: true },
                        { id: 101, name: 'Squad B', enabled: true },
                    ],
                },
            ],
        },
    ],
});

describe('EventNotificationsViewModel', () => {
    beforeEach(() => {
        post.mockClear();
        success.mockClear();
    });

    it('reads the trooper preferences from page data', () => {
        const subject = new EventNotificationsViewModel(pageData());

        expect(subject.notification_frequency).toBe('daily');
        expect(subject.push_notifications_enabled).toBe(true);
        expect(subject.organization_notifications).toHaveLength(1);
    });

    it('never notifies without page data', () => {
        const subject = new EventNotificationsViewModel();

        expect(subject.notification_frequency).toBe('never');
        expect(subject.push_notifications_enabled).toBe(false);
        expect(subject.organization_notifications).toEqual([]);
    });

    it('offers yes/no push options', () => {
        const subject = new EventNotificationsViewModel();

        expect(subject.push_notification_options.length).toBeGreaterThan(0);
    });

    it('saves a changed frequency and confirms it', async () => {
        const subject = new EventNotificationsViewModel(pageData());
        subject.notification_frequency = 'instant';

        await subject.updateNotificationFrequency();

        expect(post).toHaveBeenCalledWith(
            '/account.update-notification-frequency',
            { notification_frequency: 'instant' },
            expect.any(Object),
        );
        post.mock.calls[0][2].onSuccess({});
        expect(success).toHaveBeenCalledWith('Notification frequency updated successfully.');
    });

    it('saves the push notification choice', async () => {
        const subject = new EventNotificationsViewModel(pageData());
        subject.push_notifications_enabled = false;

        await subject.updatePushNotifications();

        expect(post).toHaveBeenCalledWith(
            '/account.update-push-notifications',
            { push_notifications_enabled: false },
            expect.any(Object),
        );
    });

    it('cascades an organization change to its regions and units', () => {
        const subject = new EventNotificationsViewModel(pageData());
        const organization = subject.organization_notifications[0];
        organization.enabled = false;

        subject.cascadeOrganizationNotification(organization);

        const region = organization.regions[0];
        expect(region.enabled).toBe(false);
        expect(region.units.every((unit) => unit.enabled === false)).toBe(true);
        expect(post).toHaveBeenCalledWith(
            '/account.update-organization-notifications',
            { organization_ids: [1, 100, 101], enabled: false },
            expect.any(Object),
        );
    });

    it('cascades a region change to its units', () => {
        const subject = new EventNotificationsViewModel(pageData());
        const region = subject.organization_notifications[0].regions[0];
        region.enabled = false;

        subject.cascadeRegionNotification(region);

        expect(region.units.every((unit) => unit.enabled === false)).toBe(true);
        expect(post).toHaveBeenCalledWith(
            '/account.update-organization-notifications',
            { organization_ids: [10, 100, 101], enabled: false },
            expect.any(Object),
        );
    });

    it('saves a single unit change on its own', () => {
        const subject = new EventNotificationsViewModel(pageData());
        const unit = subject.organization_notifications[0].regions[0].units[0];
        unit.enabled = false;

        subject.cascadeUnitNotification(unit);

        expect(post).toHaveBeenCalledWith(
            '/account.update-organization-notifications',
            { organization_ids: [100], enabled: false },
            expect.any(Object),
        );
        expect(subject.organization_notifications[0].regions[0].units[1].enabled).toBe(true);
    });

    it('confirms organization notification changes', () => {
        const subject = new EventNotificationsViewModel(pageData());

        subject.cascadeUnitNotification(subject.organization_notifications[0].regions[0].units[0]);
        post.mock.calls[0][2].onSuccess({});

        expect(success).toHaveBeenCalledWith('Organization notifications updated successfully.');
    });
});
