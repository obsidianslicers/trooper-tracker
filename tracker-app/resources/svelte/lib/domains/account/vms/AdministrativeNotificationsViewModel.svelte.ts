import type { NotificationPreferences } from "$lib/domains/account/types";
import type { Option } from "$lib/domains/types.svelte";
import { NotificationPreferenceViewModel } from "./NotificationPreferenceViewModel.svelte";

export type AdministrativeNotificationsPageData = {
    administrative_notification_enums: Option[];
    notification_preferences: NotificationPreferences;
};

export class AdministrativeNotificationsViewModel extends NotificationPreferenceViewModel {
    constructor(pageData?: AdministrativeNotificationsPageData) {
        super("Administrative", pageData?.administrative_notification_enums || [], pageData?.notification_preferences || {});
    }
}