import { ViewModel } from "$lib/domains/types.svelte";
import type { Details } from "./DetailsViewModel.svelte";

function constructAccountPageData() {
    return {
        details: {} as Details
    };
}

export type AccountPageData = {
    details: Details;
};

export class AccountViewModel extends ViewModel {
    pageData: AccountPageData;

    constructor(pageData?: AccountPageData) {
        super();
        this.pageData = pageData || constructAccountPageData();
    }
}