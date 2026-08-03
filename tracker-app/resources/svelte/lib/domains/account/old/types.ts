export type Details = {
    id: number;
    email: string;
    displayName: string;
    legalName: string;
    membershipStatus: string;
    phone: string;
    theme: string;
};

export type NotificationSettings = {
    notificationFrequency: string;
    organizationNotifications: number[];
};

export type AttachedCostume = {
    id: number;
    name: string;
    description: string;
};

export type Account = {
    details: Details;
    notifications: NotificationSettings;
    costumes: AttachedCostume[];
}
