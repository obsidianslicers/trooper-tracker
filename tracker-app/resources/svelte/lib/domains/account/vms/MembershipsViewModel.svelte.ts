import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";

export type OrganizationMembership = {
    membership_path: string;
    membership_status: string;
    identifier: string;
    image_url: string;
}

export type OrganizationRequest = {
    membership_path: string;
    status: string;
    created: string;
    updated: string;
    identifier: string;
    denial_reason: string;
    image_url: string;
}

export type MembershipsPageData = {
    organizations: Organization[];
    organization_memberships: OrganizationMembership[];
    organization_requests: OrganizationRequest[];
};

export type AddTrooperRequestForm = {
    organization_id: number | null;
    identifier: string | null;
}

export type Organization = {
    id: number;
    name: string;
    identifier_display: string;
    identifier_validation: string;
    regions: Region[];
}

export type Region = {
    id: number;
    name: string;
    units: Unit[];
}

export type Unit = {
    id: number;
    name: string;
}

export class MembershipsViewModel extends SubmitableViewModel<MembershipsViewModel, AddTrooperRequestForm> {
    is_visitor: boolean = $state(false);
    organizations: Organization[] = $state([]);
    organization_memberships: OrganizationMembership[] = $state([]);
    organization_requests: OrganizationRequest[] = $state([]);

    constructor(is_visitor: boolean, pageData?: MembershipsPageData) {
        super();
        this.is_visitor = is_visitor;
        this.organizations = pageData?.organizations ?? [];
        this.organization_memberships = pageData?.organization_memberships ?? [];
        this.organization_requests = pageData?.organization_requests ?? [];
        this.form = useForm<AddTrooperRequestForm>({
            organization_id: null,
            identifier: null
        });
    }

    addTrooperRequest = (e: Event) => {
        e.preventDefault();

        const url = getRoute('account.add-trooper-request');

        const options =
        {
            // preserveScroll: true, // Prevents page from jumping
            preserveUrl: true,     // Keeps the current URL intact
            preserveState: true,  // Keeps current local form/scroll states intact
            only: ['flash', 'results'],

            onSuccess: (page: any) => {
                // Access the direct data return value mapped to page props
                const results = page.props.results;

                this.form.defaults();

                if (results) {
                }
            }
        };

        this.form.post(url, options);
    }
}