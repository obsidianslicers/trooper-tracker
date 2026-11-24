import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { PushNotification, PushNotificationsPageData, TroopToConfirm } from "../types";

export type AttendanceAnswer = "attended" | "unabletoattend";

export class PushNotificationsViewModel {
    page: PushNotificationsPageData;

    constructor(page_data: PushNotificationsPageData) {
        this.page = page_data;
    }

    get isEmpty(): boolean {
        return this.page.notifications.length === 0 && this.page.troops_to_confirm.length === 0;
    }

    open = (notification: PushNotification) => {
        router.post(getRoute("account.push-notifications.read", { notification: notification.id }));
    };

    clearAll = () => {
        router.delete(getRoute("account.push-notifications.clear"), { preserveScroll: true });
    };

    answer = (troop: TroopToConfirm, status: AttendanceAnswer) => {
        router.post(
            getRoute("events.confirm-attendance", { event_trooper: troop.id }),
            { status },
            { preserveScroll: true },
        );
    };
}
