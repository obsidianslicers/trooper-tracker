import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";

export class LookupMembershipViewModel extends ViewModel {
    looking_up: boolean = $state(true);
    constructor() {
        super();

        this.lookupMembership();
    }


    lookupMembership = () => {
        this.looking_up = true;


        const url = getRoute('admin.troopers.requests.lookup_membership');
    }
}