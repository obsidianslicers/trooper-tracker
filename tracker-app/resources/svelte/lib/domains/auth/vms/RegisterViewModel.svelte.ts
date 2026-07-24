import { SubmitableViewModel, type Option } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import { AuthFactory } from "../factories";
import type { AuthConfiguration } from "../types";

export type Organization = {
    id: number;
    name: string;
    identifier_display: string;
    requires_guardian: boolean;
    regions: Regions[];
}

export type Regions = {
    id: number;
    name: string;
    units: Units[];
}

export type Units = {
    id: number;
    name: string;
}

export type OrganizationSelectionInput = {
    selected: boolean;
    identifier: string | null;
    region_id: string | number | null;
    unit_id: string | number | null;
};

export type Registration = {
    email: string | null;
    password: string | null;
    display_name: string | null;
    legal_name: string | null;
    membership_role: "visitor" | "member" | "handler" | null;
    date_of_birth: string | null;
    guardian_email: string | null;
    /**
     * Keyed by Organization ID: Record<number, OrganizationSelectionInput>
     * e.g., { 1: { selected: true, identifier: 'TK-1234', region_id: 5, unit_id: 12 } }
     */
    organizations: Record<number, OrganizationSelectionInput>;
};

export type RegisterPageData = {
    membership_roles: Option[];
    organizations: Organization[];
    oauth: AuthConfiguration;
};

export class RegisterViewModel extends SubmitableViewModel<RegisterViewModel, Registration> {
    #useOAuthEmail = false;
    membership_roles = $state<Option[]>([]);
    organizations = $state<Organization[]>([]);

    constructor(pageData?: RegisterPageData) {
        super();
        // Initialize Inertia's useForm hook directly inside the instance
        this.form = useForm<Registration>(AuthFactory.registration());
        if (pageData) {
            this.form.email = pageData.oauth?.session?.email || '';
            this.membership_roles = pageData.membership_roles;
            this.organizations = pageData.organizations;

            if (this.form.email && this.form.email.length > 0) {
                this.#useOAuthEmail = true;
            }

            for (let i = 0; i < this.organizations.length; i++) {
                const org = this.organizations[i];
                this.form.organizations[org.id] = {
                    selected: false,
                    identifier: null,
                    region_id: null,
                    unit_id: null
                };
            }

            this.form.defaults();
        }
    }

    submit = async (e: Event) => {
        e.preventDefault();

        const url = getRoute('auth.register');

        this.form.post(url, {});
    };

    /**
     * Determines if the currently selected organizations require a guardian based on the user's membership role
     * @returns True if a guardian is required, false otherwise
     */
    requiresGuardian(): boolean {
        if (this.form.membership_role !== 'member') {
            return false;
        }
        return this.organizations.some(org => {
            const orgForm = this.form.organizations?.[org.id];
            return orgForm?.selected && org.requires_guardian;
        });
    }

    /**
     * Checks if the email was pre-filled via OAuth session
     * @returns True if the email was pre-filled via OAuth, false otherwise
     */
    useOAuthEmail() {
        return this.#useOAuthEmail;
    }

    /**
     * Toggles the selection of an organization and applies auto-selection logic for regions and units based
     * on the organization's metadata.
     * 
     * @param orgId The ID of the organization to toggle
     * @returns void
     */
    toggleOrganization(orgId: number): void {
        const orgForm = this.form.organizations?.[orgId];
        if (!orgForm) {
            return;
        }

        // If unchecked, reset nested selections
        if (!orgForm.selected) {
            orgForm.region_id = null;
            orgForm.unit_id = null;
            return;
        }

        // Find the matching raw organization meta data data
        const org = this.organizations.find(o => o.id === orgId);
        if (!org) {
            return;
        }

        // 1. If there's exactly one region, auto-select it
        if (org.regions && org.regions.length === 1) {
            const singleRegion = org.regions[0];
            orgForm.region_id = singleRegion.id;

            // 2. If that single region has exactly one unit, auto-select it too
            if (singleRegion.units && singleRegion.units.length === 1) {
                orgForm.unit_id = singleRegion.units[0].id;
            }
        }
    }

    /**
     * Helper method to check if an organization is selected based on the current form state
     * 
     * @param orgId The ID of the organization to check
     * @returns True if the organization is selected, false otherwise
     */
    isOrganizationSelected(orgId: number): boolean {
        return this.form.organizations?.[orgId]?.selected || false;
    }

    /**
     * Resets the unit selection when the region changes
     *
     * @param orgId The ID of the organization
     */
    resetOnRegionChange(orgId: number) {
        if (this.form.organizations?.[orgId]) {
            this.form.organizations[orgId].unit_id = null;
        }
    }

    /**
     * Helper method to retrieve errors for a specific organization field, if they exist
     * 
     * @param orgId The ID of the organization
     * @param key The specific field key within the organization
     * @returns An array of error messages for the specified field
     */
    getErrors(orgId: number, key: string): string | string[] {
        if (this.errors[`organizations.${orgId}.${key}`]) {
            return this.errors[`organizations.${orgId}.${key}`];
        }

        return [];
    }

    /**
     * Get regions for an organization transformed into UI Options
     */
    getRegions(orgId: number): Option[] {
        const org = this.organizations.find(o => o.id === orgId);
        if (!org || !org.regions) {
            return [];
        }

        return org.regions.map(region => ({
            value: region.id,
            label: region.name
        }));
    }

    /**
     * Get units based on the currently selected region within an organization
     */
    getUnits(orgId: number): Option[] {
        const org = this.organizations.find(o => o.id === orgId);
        // Safely check what region is currently chosen in the Inertia form state
        const selectedRegionId = this.form.organizations?.[orgId]?.region_id;

        if (!org || !selectedRegionId) {
            return [];
        }

        const region = org.regions.find(r => r.id === Number(selectedRegionId));
        if (!region || !region.units) {
            return [];
        }

        return region.units.map(unit => ({
            value: unit.id,
            label: unit.name
        }));
    }
}

