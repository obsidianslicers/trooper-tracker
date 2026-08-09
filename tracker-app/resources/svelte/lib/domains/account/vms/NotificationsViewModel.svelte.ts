import { ViewModel, type Option } from "$lib/domains/types.svelte";
import type { EventNotificationsPageData } from "./EventNotificationsViewModel.svelte";

export type NotificationsPageData = EventNotificationsPageData & {
    is_administrator: boolean;
    trooper_notification_enums: Option[];
    administrative_notification_enums: Option[];
    notification_preferences: Record<string, Record<string, boolean>>;
};

export class NotificationsViewModel extends ViewModel {
    page_data: NotificationsPageData = {} as NotificationsPageData;

    constructor(pageData?: NotificationsPageData) {
        super();
        this.page_data = pageData || {} as NotificationsPageData;
    }
}