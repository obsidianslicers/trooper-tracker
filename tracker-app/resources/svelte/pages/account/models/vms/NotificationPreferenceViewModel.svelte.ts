import { ViewModel, type Option } from "$lib/domains/types.svelte";
import toastStateSvelte from "$lib/states/toast-state.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { NotificationPreferences } from "../types";

export type NotificationSubsection = "Administrative" | "Trooper" | "";

export class NotificationPreferenceViewModel extends ViewModel {
    subsection: NotificationSubsection = $state("");
    notification_enums: Option[] = $state([]);
    notification_preferences: NotificationPreferences = $state({});

    constructor(subsection: NotificationSubsection, notification_enums: Option[], notification_preferences: NotificationPreferences) {
        super();
        this.subsection = subsection;
        this.notification_enums = notification_enums || [];
        this.notification_preferences = notification_preferences || {};

        //  missing values mean "enabled" server-side; every channel must be a boolean for bind:checked
        for (const notification of this.notification_enums) {
            const key = notification.value as string;
            this.notification_preferences[key] = {
                mail: true,
                fcm: true,
                database: true,
                ...this.notification_preferences[key],
            };
        }
    }

    updateMailNotificationPreference = (notification: string): void => {
        this.updateNotificationPreference(notification, "mail", this.notification_preferences[notification].mail);
    }

    updateFcmNotificationPreference = (notification: string): void => {
        this.updateNotificationPreference(notification, "fcm", this.notification_preferences[notification].fcm);
    }

    updateDatabaseNotificationPreference = (notification: string): void => {
        this.updateNotificationPreference(notification, "database", this.notification_preferences[notification].database);
    }

    private updateNotificationPreference(notification: string, channel: string, enabled: boolean): void {
        const url = getRoute('account.update-notification-preference');

        //  fire & forget the request, but we want to preserve the current URL and state
        const options = createPartialReloadOptions({
            onSuccess: (page: any) => {
                toastStateSvelte.success(`${this.subsection} Notification Preference updated successfully.`);
            }
        });

        const data = {
            notification: notification,
            channel: channel,
            enabled: enabled,
        };

        router.post(url, data, options);
    }
}