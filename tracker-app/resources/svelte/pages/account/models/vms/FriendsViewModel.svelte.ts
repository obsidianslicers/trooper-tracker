import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";

export type Friend = {
    id: number;
    legal_name: string;
    display_name: string;
};

export type FriendsPageData = Friend[];

export class FriendsViewModel extends ViewModel {
    friends: Friend[] = $state([]);

    constructor(pageData: FriendsPageData) {
        super();
        this.friends = pageData || [];
    }

    getServiceRecordUrl = (friend: Friend): string => {
        return getRoute('service-records.trooper', { trooper: friend.id });
    }
}