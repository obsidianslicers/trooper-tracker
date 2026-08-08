import { ViewModel } from "$lib/domains/types.svelte";
import type { DetailsPageData } from "./DetailsViewModel.svelte";

function constructAccountPageData() {
    return {
        email: "",
        details: {} as DetailsPageData
    };
}

export type AccountPageData = {
    email: string;
    details: DetailsPageData;
};

export class AccountViewModel extends ViewModel {
    pageData: AccountPageData;

    constructor(pageData?: AccountPageData) {
        super();
        this.pageData = pageData || constructAccountPageData();
    }

    get email(): string { return this.pageData.email; }
}