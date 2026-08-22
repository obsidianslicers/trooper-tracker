import { SubmitableViewModel, type Option } from "$lib/domains/types.svelte";
import toastStateSvelte from "$lib/states/toast-state.svelte";
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
    organization_options: Option[];
    organization_memberships: OrganizationMembership[];
    organization_requests: OrganizationRequest[];
};

export type AddTrooperRequestForm = {
    organization_id: number | null;
    identifier: string | null;
}

export type OrganizationItem = {
    id: number;
    name: string;
    parent_id?: number;
    primary_organization_id?: number;
}

export type Organization = OrganizationItem & {
    identifier_display: string;
    identifier_validation: string;
    regions: Region[];
}

export type Region = OrganizationItem & {
    units: Unit[];
}

export type Unit = OrganizationItem & {
}

export class MembershipsViewModel extends SubmitableViewModel<MembershipsViewModel, AddTrooperRequestForm> {
    is_visitor: boolean = $state(false);
    organizations: Organization[] = $state([]);
    organization_options: Option[] = $state([]);
    organization_lookups: Record<number, OrganizationItem> = $state({});
    organization_memberships: OrganizationMembership[] = $state([]);
    organization_requests: OrganizationRequest[] = $state([]);

    constructor(is_visitor: boolean, pageData?: MembershipsPageData) {
        super();
        this.is_visitor = is_visitor;
        this.organizations = pageData?.organizations || [];
        this.organization_options = pageData?.organization_options || [];
        this.organization_memberships = pageData?.organization_memberships ?? [];
        this.organization_requests = pageData?.organization_requests ?? [];
        this.form = useForm<AddTrooperRequestForm>({
            organization_id: null,
            identifier: null
        });

        for (const org of this.organizations) {
            this.organization_lookups[org.id] = org;
            for (const region of org.regions) {
                this.organization_lookups[region.id] = org;
                for (const unit of region.units) {
                    this.organization_lookups[unit.id] = org;
                }
            }
        }
    }

    get selected_identifier_label(): string | null {
        if (this.selected_primary_organization === null) {
            return null;
        }
        return this.selected_primary_organization.name + ' ' + this.selected_primary_organization.identifier_display;
    }

    get selected_primary_organization(): Organization | null {
        const id = this.form.organization_id || 0;
        const org = this.organization_lookups[id];

        if (org) {
            if (org.primary_organization_id) {
                return this.organization_lookups[org.primary_organization_id] as Organization || null;
            }
            return org as Organization;
        }

        return null;
    }

    addTrooperRequest = (e: Event) => {
        e.preventDefault();

        const url = getRoute('account.add-trooper-request');

        const options =
        {
            preserveScroll: 'errors' as any, // Prevents page from jumping
            preserveUrl: true,     // Keeps the current URL intact
            preserveState: true,  // Keeps current local form/scroll states intact
            only: ['flash', 'results'],

            onSuccess: (page: any) => {
                // Access the direct data return value mapped to page props
                const results = page.props.results;

                this.form.defaults();

                toastStateSvelte.success('Trooper request created successfully.');

                if (results) {
                    this.organization_requests = results.organization_requests || [];
                }
            }
        };

        this.form.post(url, options);
    }
}