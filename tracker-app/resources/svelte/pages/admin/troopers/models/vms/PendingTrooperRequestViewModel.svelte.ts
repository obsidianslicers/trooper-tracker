import { ViewModel } from "$lib/domains/types.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";

export type TrooperRequest = {
    id: number;
    identifier: string;
    status: string;
    denial_reason: string | null;
    organization: Organization;
    primary_organization: Organization;
    trooper: Trooper;
};

type Organization = {
    id: number;
    name: string;
    parent_name: string | null;
};

type Trooper = {
    id: number;
    display_name: string;
    legal_name: string;
    email: string;
    phone: string;
};

export class PendingTrooperRequestViewModel extends ViewModel {
    //  already has $state from the parent
    submitted: boolean = $state(false);
    submitting: boolean = $state(false);
    denying: boolean = $state(false);
    denial_reason: string | null = $state(null);
    request: TrooperRequest;

    constructor(pageData: TrooperRequest) {
        super();
        this.request = pageData;
    }

    deny = (e: Event) => {
        e.preventDefault();

        this.submitting = true;

        const parms = { trooper_request: this.request.id };
        const url = getRoute('admin.troopers.approvals.deny-request', parms);

        const options = createPartialReloadOptions({
            onFinish: () => {
                this.submitted = true;
            }
        });

        const data = {
            denial_reason: this.denial_reason,
        };

        router.post(url, data, options);
    }

    approve = () => {
        this.submitting = true;

        const parms = { trooper_request: this.request.id };
        const url = getRoute('admin.troopers.approvals.approve-request', parms);

        const options = createPartialReloadOptions({
            onFinish: () => {
                this.submitted = true;
            }
        });

        const data = {};

        router.post(url, data, options);
    }
}