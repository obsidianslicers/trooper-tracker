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
    selected: boolean;
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

    constructor(pageData?: EventNotificationsPageData) {
        super();
        this.push_notification_options = YES_NO_OPTIONS;
        this.push_notifications_enabled = pageData?.push_notifications_enabled || false;
        this.notification_frequency = pageData?.notification_frequency || "never";
        this.notification_frequency_enums = pageData?.notification_frequency_enums || [];
        this.organization_notifications = pageData?.organization_notifications || [];
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
        // Cascade the selection state to all regions and units under this organization
        organization_notification.regions.forEach(region => {
            region.selected = organization_notification.selected;
            this.cascadeRegionNotification(region);
        });
    }

    cascadeRegionNotification = (region_notification: RegionNotification) => {
        // Cascade the selection state to all units under this region
        region_notification.units.forEach(unit => {
            unit.selected = region_notification.selected;
        });
    }
}