import { RevertibleViewModel, ViewModel } from '../types.svelte';
import { updateDetails } from './commands';
import { getAccount } from './queries';
import type { Details, NotificationSettings } from './types';
import { AccountFactory } from './values';


export class AccountViewModel extends ViewModel<AccountViewModel> {
    account = $state(AccountFactory.defaultProfile());

    async load(): Promise<AccountViewModel> {
        this.account = await getAccount();

        return this;
    }
}


export class DetailsViewModel extends RevertibleViewModel<DetailsViewModel, Details> {
    details = $state(AccountFactory.defaultDetails());

    protected get source(): Details {
        return this.details;
    }

    protected set source(value: Details) {
        this.details = value;
    }

    load(): Promise<DetailsViewModel>;
    load(details: Details): Promise<DetailsViewModel>;
    async load(details?: Details): Promise<DetailsViewModel> {
        if (details) {
            this.details = details;
            this.original = details;
        }
        return this;
    }

    update = async (): Promise<void> => {
        this.submitting = true;
        await updateDetails(this.details)
            .then(() => {
                this.original = this.details;
            })
            .catch(this.validationErrorHandler);
        this.submitting = false;
    };
}


export class NotificationsViewModel extends RevertibleViewModel<NotificationsViewModel, NotificationSettings> {
    notifications = $state(AccountFactory.defaultNotificationSettings());

    protected get source(): NotificationSettings {
        return this.notifications;
    }

    protected set source(value: NotificationSettings) {
        this.notifications = value;
    }

    load(): Promise<NotificationsViewModel>;
    load(notifications: NotificationSettings): Promise<NotificationsViewModel>;
    async load(notifications?: NotificationSettings): Promise<NotificationsViewModel> {
        if (notifications) {
            this.notifications = notifications;
            this.original = notifications;
        }
        return this;
    }
}