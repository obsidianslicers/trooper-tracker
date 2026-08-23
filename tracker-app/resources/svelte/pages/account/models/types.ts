export type Account = {};

export type NotificationChannels = { mail: boolean; fcm: boolean; database: boolean };

export type NotificationPreferences = Record<string, Record<string, boolean> | NotificationChannels>;