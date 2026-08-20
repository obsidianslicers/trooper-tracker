import { ViewModel } from "$lib/domains/types.svelte";
import type { CostumesPageData } from "./CostumesViewModel.svelte";
import type { DetailsPageData } from "./DetailsViewModel.svelte";
import type { FriendsPageData } from "./FriendsViewModel.svelte";
import type { MembershipsPageData } from "./MembershipsViewModel.svelte";
import type { MinorsPageData } from "./MinorsViewModel.svelte";
import type { NotificationsPageData } from "./NotificationsViewModel.svelte";

function constructAccountPageData() {
    return {
        trooper_id: 0,
        is_visitor: false,
        email: "",
        details: {} as DetailsPageData,
        notifications: {} as NotificationsPageData,
        memberships: {} as MembershipsPageData,
        costumes: {} as CostumesPageData,
        friends: {} as FriendsPageData,
        minors: {} as MinorsPageData,
    };
}

export type AccountPageData = {
    trooper_id: number;
    is_visitor: boolean;
    email: string;
    details: DetailsPageData;
    notifications: NotificationsPageData;
    memberships: MembershipsPageData;
    costumes: CostumesPageData;
    friends: FriendsPageData;
    minors: MinorsPageData;
};

export class AccountViewModel extends ViewModel {
    pageData: AccountPageData;

    constructor(pageData?: AccountPageData) {
        super();
        this.pageData = pageData || constructAccountPageData();
    }

    get email(): string { return this.pageData.email; }
}