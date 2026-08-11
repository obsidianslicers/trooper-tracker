import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";

export type Friend = {
    id: number;
    legal_name: string;
    display_name: string;
};

export class FriendsViewModel extends ViewModel {
    friends: Friend[] = $state([]);

    constructor(pageData?: Friend[]) {
        super();
        this.friends = pageData?.length ? pageData : [];
    }

    getServiceRecordUrl = (friend: Friend): string => {
        return getRoute('service-records.trooper', { trooper: friend.id });
    }
}