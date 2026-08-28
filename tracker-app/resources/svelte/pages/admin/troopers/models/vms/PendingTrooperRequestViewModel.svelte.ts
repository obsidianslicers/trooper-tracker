import { ViewModel } from "$lib/domains/types.svelte";

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
    submitting: boolean = $state(false);
    denying: boolean = $state(false);
    request: TrooperRequest;

    constructor(pageData: TrooperRequest) {
        super();
        this.request = pageData;
    }
}