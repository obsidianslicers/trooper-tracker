import { ViewModel } from "$lib/domains/types.svelte";
import type { Details } from "./DetailsViewModel.svelte";

function constructAccountPageData() {
    return {
        email: "",
        details: {} as Details
    };
}

export type AccountPageData = {
    email: string;
    details: Details;
};

export class AccountViewModel extends ViewModel {
    pageData: AccountPageData;

    constructor(pageData?: AccountPageData) {
        super();
        this.pageData = pageData || constructAccountPageData();
    }

    get email(): string { return this.pageData.email; }
}