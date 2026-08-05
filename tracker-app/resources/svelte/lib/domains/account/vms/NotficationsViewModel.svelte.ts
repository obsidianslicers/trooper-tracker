import { SubmitableViewModel, type Option } from "$lib/domains/types.svelte";
import { useForm, type InertiaForm } from "@inertiajs/svelte";

function createNotificationsForm(options: Partial<Notifications> = {}): InertiaForm<Notifications> {
    const data = {
        trooper_notification_enums: [],
        administrative_notification_enums: [],
        notification_frequency: "instant",
        push_notifications_enabled: true,
        organization_notifications: [],
        notification_preferences: {},
        ...options
    };

    return useForm<Notifications>(data);
}

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

export type Notifications = {
    trooper_notification_enums: Option[];
    administrative_notification_enums: Option[];
    notification_frequency: string;
    push_notifications_enabled: boolean;
    organization_notifications: OrganizationNotifications[];
    notification_preferences: Record<string, Record<string, boolean>>;
};


export class NotificationsViewModel extends SubmitableViewModel<NotificationsViewModel, Notifications> {
}