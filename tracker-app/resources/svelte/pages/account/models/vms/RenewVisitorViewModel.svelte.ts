import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { formatDateTime, getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";

export type RenewVisitorPageData = {
    visitor_expires_at: string | null;
};

export class RenewVisitorViewModel extends SubmitableViewModel<RenewVisitorViewModel, {}> {
    pageData: RenewVisitorPageData;

    constructor(pageData: RenewVisitorPageData) {
        super();
        this.pageData = pageData;
        this.form = useForm({});
    }

    get visitor_expires_at(): string | null { return formatDateTime(this.pageData.visitor_expires_at); }

    renew = (e: Event) => {
        e.preventDefault();

        const url = getRoute('account.renew-visitor-membership');

        const options = {};

        this.form.post(url, options);
    }
}