import { ViewModel } from "$lib/domains/types.svelte";
import type { DetailsPageData } from "./DetailsViewModel.svelte";
import type { NotificationsPageData } from "./NotificationsViewModel.svelte";

function constructAccountPageData() {
    return {
        email: "",
        details: {} as DetailsPageData,
        notifications: {} as NotificationsPageData
    };
}

export type AccountPageData = {
    email: string;
    details: DetailsPageData;
    notifications: NotificationsPageData;
};

export class AccountViewModel extends ViewModel {
    pageData: AccountPageData;

    constructor(pageData?: AccountPageData) {
        super();
        this.pageData = pageData || constructAccountPageData();
    }

    get email(): string { return this.pageData.email; }
}