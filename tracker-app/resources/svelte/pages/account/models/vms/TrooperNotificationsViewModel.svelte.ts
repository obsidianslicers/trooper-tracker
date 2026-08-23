import type { NotificationPreferences } from "$lib/domains/account/types";
import type { Option } from "$lib/domains/types.svelte";
import { NotificationPreferenceViewModel } from "./NotificationPreferenceViewModel.svelte";

export type TrooperNotificationsPageData = {
    trooper_notification_enums: Option[];
    notification_preferences: NotificationPreferences;
};

export class TrooperNotificationsViewModel extends NotificationPreferenceViewModel {
    constructor(pageData?: TrooperNotificationsPageData) {
        super("Trooper", pageData?.trooper_notification_enums || [], pageData?.notification_preferences || {});
    }
}