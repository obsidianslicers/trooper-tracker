import { ViewModel } from "$lib/domains/types.svelte";
import type { DetailsPageData } from "./DetailsViewModel.svelte";
import type { Friend } from "./FriendsViewModel.svelte";
import type { Minor } from "./MinorsViewModel.svelte";
import type { NotificationsPageData } from "./NotificationsViewModel.svelte";

function constructAccountPageData() {
    return {
        email: "",
        details: {} as DetailsPageData,
        notifications: {} as NotificationsPageData,
        friends: [] as Friend[],
        minors: [] as Minor[],
    };
}

export type AccountPageData = {
    email: string;
    details: DetailsPageData;
    notifications: NotificationsPageData;
    friends: Friend[];
    minors: Minor[];
};

export class AccountViewModel extends ViewModel {
    pageData: AccountPageData;

    constructor(pageData?: AccountPageData) {
        super();
        this.pageData = pageData || constructAccountPageData();
    }

    get email(): string { return this.pageData.email; }
}