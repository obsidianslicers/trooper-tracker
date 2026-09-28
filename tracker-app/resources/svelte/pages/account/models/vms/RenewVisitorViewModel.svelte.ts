import { ViewModel } from "$lib/domains/types.svelte";

export type RenewVisitorPageData = {
    visitor_expires_at: string | null;
};

export class RenewVisitorViewModel extends ViewModel {
    pageData: RenewVisitorPageData;

    constructor(pageData: RenewVisitorPageData) {
        super();
        this.pageData = pageData;
    }

    get visitor_expires_at(): string | null { return this.pageData.visitor_expires_at; }
}