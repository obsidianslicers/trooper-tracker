import { SubmitableViewModel, type Option } from "$lib/domains/types.svelte";
import toastStateSvelte from "$lib/states/toast-state.svelte";
import { getRoute, propertyRemover } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";

function createNotificationsForm(options: Partial<NotificationsForm> = {}): InertiaForm<NotificationsForm> {
    const data = {
        notification_frequency: "instant",
        push_notifications_enabled: true,
        organization_notifications: [],
        notification_preferences: {},
        ...options
    };

    propertyRemover(data, [
        'trooper_notification_enums',
        'administrative_notification_enums',
        'notification_frequency_enums'
    ]);

    return useForm<NotificationsForm>(data);
}

export type NotificationsForm = {
    notification_frequency: string;
    push_notifications_enabled: boolean;
    organization_notifications: OrganizationNotifications[];
    notification_preferences: Record<string, Record<string, boolean>>;
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

export type NotificationsPageData = NotificationsForm & {
    trooper_notification_enums: Option[];
    administrative_notification_enums: Option[];
    notification_frequency_enums: Option[];
};

export class NotificationsViewModel extends SubmitableViewModel<NotificationsViewModel, NotificationsForm> {
    trooper_notification_enums: Option[] = $state([]);
    administrative_notification_enums: Option[] = $state([]);
    notification_frequency_enums: Option[] = $state([]);


    constructor(pageData?: NotificationsPageData) {
        super();
        // Initialize Inertia's useForm hook directly inside the instance
        this.form = createNotificationsForm(pageData);
        this.trooper_notification_enums = pageData?.trooper_notification_enums || [];
        this.administrative_notification_enums = pageData?.administrative_notification_enums || [];
        this.notification_frequency_enums = pageData?.notification_frequency_enums || [];
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

        this.form.post(url, options);
    }
}