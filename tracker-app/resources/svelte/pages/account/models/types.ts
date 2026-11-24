import type { UnreadNotice } from "$lib/domains/types.svelte";

export type Account = {};

export type NotificationChannels = { mail: boolean; fcm: boolean; database: boolean };

export type NotificationPreferences = Record<string, Record<string, boolean> | NotificationChannels>;

/** The notices page (NoticesPageData). */
export type NoticesPageData = {
    notices: UnreadNotice[];
};

/** One row of the notifications inbox (PushNotificationResource). */
export type PushNotification = {
    id: string;
    title: string;
    body: string;
    is_unread: boolean;
    sent_ago: string;
};

/** A troop waiting for "did you make it?" (TroopToConfirmResource). */
export type TroopToConfirm = {
    id: number;
    event_name: string;
    shift_time: string;
    url: string;
};

/** The notifications inbox (PushNotificationsPageData). */
export type PushNotificationsPageData = {
    notifications: PushNotification[];
    troops_to_confirm: TroopToConfirm[];
};
