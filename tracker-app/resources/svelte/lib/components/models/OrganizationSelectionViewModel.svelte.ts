import { SubmitableViewModel, type Option } from "$lib/domains/types.svelte";

/** A club a trooper can pick, with its regions and units (RegisterOrganizationResource). */
export type SelectableOrganization = {
    id: number;
    name: string;
    identifier_display: string;
    requires_guardian: boolean;
    regions: SelectableRegion[];
};

export type SelectableRegion = {
    id: number;
    name: string;
    units: { id: number; name: string }[];
};

export type OrganizationSelectionInput = {
    selected: boolean;
    identifier: string | null;
    region_id: string | number | null;
    unit_id: string | number | null;
};

/** A form that picks clubs, keyed by organization id. */
export type OrganizationSelectionForm = {
    organizations: Record<number, OrganizationSelectionInput>;
};

/**
 * Picking clubs, then a region and unit in each: shared by registration and the denied
 * application's resubmit form (OrganizationSelection.svelte).
 */
export abstract class OrganizationSelectionViewModel<
    T,
    TForm extends OrganizationSelectionForm,
> extends SubmitableViewModel<T, TForm> {
    organizations = $state<SelectableOrganization[]>([]);

    /** A blank choice for every club, for a form's starting values. */
    static blankSelections(
        organizations: SelectableOrganization[],
    ): Record<number, OrganizationSelectionInput> {
        return Object.fromEntries(
            organizations.map((org) => [
                org.id,
                { selected: false, identifier: null, region_id: null, unit_id: null },
            ]),
        );
    }

    /**
     * Unpicking a club clears its region and unit; picking one with a single region (and that
     * region a single unit) picks those for the trooper.
     */
    toggleOrganization(orgId: number): void {
        const orgForm = this.form.organizations?.[orgId];
        if (!orgForm) {
            return;
        }

        if (!orgForm.selected) {
            orgForm.region_id = null;
            orgForm.unit_id = null;
            return;
        }

        const org = this.organizations.find((o) => o.id === orgId);
        if (org?.regions?.length === 1) {
            const region = org.regions[0];
            orgForm.region_id = region.id;

            if (region.units?.length === 1) {
                orgForm.unit_id = region.units[0].id;
            }
        }
    }

    isOrganizationSelected(orgId: number): boolean {
        return this.form.organizations?.[orgId]?.selected || false;
    }

    /** A new region means the unit picked under the old one no longer applies. */
    resetOnRegionChange(orgId: number) {
        if (this.form.organizations?.[orgId]) {
            this.form.organizations[orgId].unit_id = null;
        }
    }

    getErrors(orgId: number, key: string): string | string[] {
        return this.errors[`organizations.${orgId}.${key}`] ?? [];
    }

    getRegions(orgId: number): Option[] {
        const org = this.organizations.find((o) => o.id === orgId);

        return (org?.regions ?? []).map((region) => ({ value: region.id, label: region.name }));
    }

    /** The units of the region picked in a club. */
    getUnits(orgId: number): Option[] {
        const org = this.organizations.find((o) => o.id === orgId);
        const regionId = this.form.organizations?.[orgId]?.region_id;

        if (!org || !regionId) {
            return [];
        }

        const region = org.regions.find((r) => r.id === Number(regionId));

        return (region?.units ?? []).map((unit) => ({ value: unit.id, label: unit.name }));
    }
}
