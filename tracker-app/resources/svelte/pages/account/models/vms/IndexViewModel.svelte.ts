import { ViewModel } from "$lib/domains/types.svelte";
import { formatDate, formatDateTime } from "$lib/utils";
import type { CostumesPageData } from "./CostumesViewModel.svelte";
import type { DetailsPageData } from "./DetailsViewModel.svelte";
import type { FriendsPageData } from "./FriendsViewModel.svelte";
import type { MembershipsPageData } from "./MembershipsViewModel.svelte";
import type { MinorsPageData } from "./MinorsViewModel.svelte";
import type { NotificationsPageData } from "./NotificationsViewModel.svelte";

export type IndexPageData = {
    trooper_id: number;
    is_visitor: boolean;
    is_handler: boolean;
    deletion_requested_at: string | null;
    visitor_expires_at: string | null;
    email: string;
    details: DetailsPageData;
    notifications: NotificationsPageData;
    memberships: MembershipsPageData;
    costumes: CostumesPageData;
    friends: FriendsPageData;
    minors: MinorsPageData;
};

export class IndexViewModel extends ViewModel {
    pageData: IndexPageData;

    constructor(pageData: IndexPageData) {
        super();
        this.pageData = pageData;
    }

    get has_deletion_request(): boolean { return this.pageData.deletion_requested_at !== null; }
    get has_minors(): boolean { return this.pageData.minors?.length > 0; }
    get has_friends(): boolean { return this.pageData.friends?.length > 0; }
    get is_handler(): boolean { return this.pageData.is_handler; }
    get is_visitor(): boolean { return this.pageData.is_visitor; }
    get email(): string { return this.pageData.email; }

    get deletion_date(): string | null {
        if (!this.pageData.deletion_requested_at) return null;
        const deadline = new Date(this.pageData.deletion_requested_at);
        deadline.setDate(deadline.getDate() + 30);
        return formatDate(deadline);
    }

    get visitation_expiration_datetime(): string | null {
        if (!this.pageData.visitor_expires_at) return null;
        const deadline = new Date(this.pageData.visitor_expires_at);
        return formatDateTime(deadline);
    }
}