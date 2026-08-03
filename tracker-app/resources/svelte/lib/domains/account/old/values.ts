import type { Account, AttachedCostume, Details, NotificationSettings } from './types';

export const AccountFactory = {
    defaultDetails(options: Partial<Details> = {}): Details {
        return {
            id: 0,
            displayName: '',
            legalName: '',
            phone: '',
            theme: 'stormtrooper',
            membershipStatus: 'pending',
            email: '',
            ...options
        };
    },

    defaultNotificationSettings(options: Partial<NotificationSettings> = {}): NotificationSettings {
        return {
            notificationFrequency: 'never',
            organizationNotifications: [],
            ...options
        };
    },

    defaultProfile(options: Partial<Account> = {}): Account {
        return {
            details: AccountFactory.defaultDetails(options.details || {}),
            notifications: {
                notificationFrequency: 'never',
                organizationNotifications: []
            },
            costumes: [] as AttachedCostume[],
            ...options
        };
    },
};