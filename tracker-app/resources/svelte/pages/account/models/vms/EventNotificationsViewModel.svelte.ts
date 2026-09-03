import { YES_NO_OPTIONS } from "$lib/constants";
import { ViewModel, type Option } from "$lib/domains/types.svelte";
import toastStateSvelte from "$lib/states/toast-state.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";

export type EventNotificationsForm = {
    notification_frequency: string;
    push_notifications_enabled: boolean;
    organization_notifications: OrganizationNotifications[];
};

export type OrganizationNotification = {
    id: number;
    name: string;
    enabled: boolean;
}

export type OrganizationNotifications = OrganizationNotification & {
    regions: RegionNotification[];
}

export type RegionNotification = OrganizationNotification & {
    units: UnitNotification[];
}

export type UnitNotification = OrganizationNotification;

export type EventNotificationsPageData = EventNotificationsForm & {
    notification_frequency_enums: Option[];
    notification_frequency: string;
    push_notifications_enabled: boolean;
    organization_notifications: OrganizationNotifications[];
};

export class EventNotificationsViewModel extends ViewModel {
    push_notification_options: Option[] = $state([]);
    push_notifications_enabled: boolean = $state(false);
    notification_frequency_enums: Option[] = $state([]);
    notification_frequency: string = $state("never");
    organization_notifications: OrganizationNotifications[] = $state([]);

    constructor(pageData: EventNotificationsPageData) {
        super();
        this.push_notification_options = YES_NO_OPTIONS;
        this.push_notifications_enabled = pageData.push_notifications_enabled;
        this.notification_frequency = pageData.notification_frequency;
        this.notification_frequency_enums = pageData.notification_frequency_enums;
        this.organization_notifications = pageData.organization_notifications;
    }

    updateNotificationFrequency = async () => {
        const url = getRoute('account.update-notification-frequency');

        //  fire & forget the request, but we want to preserve the current URL and state
        const options =
        {
            preserveUrl: true,    // Keeps the current URL intact
            preserveState: true,  // Keeps current local form/scroll states intact
            preserveScroll: true, // Prevents page from jumping

            onSuccess: (page: any) => {
                toastStateSvelte.success("Notification frequency updated successfully.");
            }
        };

        const data = {
            notification_frequency: this.notification_frequency,
        };

        router.post(url, data, options);
    }

    updatePushNotifications = async () => {
        const url = getRoute('account.update-push-notifications');

        //  fire & forget the request, but we want to preserve the current URL and state
        const options =
        {
            preserveUrl: true,    // Keeps the current URL intact
            preserveState: true,  // Keeps current local form/scroll states intact
            preserveScroll: true, // Prevents page from jumping

            onSuccess: (page: any) => {
                toastStateSvelte.success("Push notifications updated successfully.");
            }
        };

        const data = {
            push_notifications_enabled: this.push_notifications_enabled,
        };

        router.post(url, data, options);
    }

    cascadeOrganizationNotification = (organization_notification: OrganizationNotifications) => {
        const organization_ids = [
            organization_notification.id,
            ...organization_notification.regions.flatMap(region => region.units.map(unit => unit.id))
        ];
        this.fireOrganizationNotification(organization_ids, organization_notification.enabled);
        organization_notification.regions.forEach(region => {
            region.enabled = organization_notification.enabled;
            this.cascadeRegionNotification(region);
        });
    }

    cascadeRegionNotification = (region_notification: RegionNotification) => {
        const organization_ids = [
            region_notification.id,
            ...region_notification.units.map(unit => unit.id)
        ];
        this.fireOrganizationNotification(organization_ids, region_notification.enabled);
        region_notification.units.forEach(unit => {
            unit.enabled = region_notification.enabled;
        });
    }

    cascadeUnitNotification = (unit_notification: UnitNotification) => {
        this.fireOrganizationNotification([unit_notification.id], unit_notification.enabled);
    }

    private fireOrganizationNotification(organization_ids: number[], enabled: boolean): void {
        // FIRE & FORGET POST
        const url = getRoute('account.update-organization-notifications');

        const options =
        {
            preserveUrl: true,    // Keeps the current URL intact
            preserveState: true,  // Keeps current local form/scroll states intact
            preserveScroll: true, // Prevents page from jumping

            onSuccess: (page: any) => {
                toastStateSvelte.success("Organization notifications updated successfully.");
            }
        };


        const data = {
            organization_ids,
            enabled,
        };

        router.post(url, data, options);
    }
}