import {
    OrganizationSelectionViewModel,
    type OrganizationSelectionForm,
} from "$lib/components/models/OrganizationSelectionViewModel.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { DeniedPageData } from "../onboarding-types";

/**
 * Resubmitting a denied application: the clubs it asked for start picked, with the ID,
 * region and unit given before.
 */
export class DeniedViewModel extends OrganizationSelectionViewModel<
    DeniedViewModel,
    OrganizationSelectionForm
> {
    constructor(page: DeniedPageData) {
        super();
        this.organizations = page.organizations;

        const organizations = OrganizationSelectionViewModel.blankSelections(page.organizations);

        for (const request of page.denied_requests) {
            if (organizations[request.organization_id]) {
                organizations[request.organization_id] = {
                    selected: true,
                    identifier: request.identifier,
                    region_id: request.region_id,
                    unit_id: request.unit_id,
                };
            }
        }

        this.form = useForm<OrganizationSelectionForm>({ organizations });
    }

    submit = (e: Event) => {
        e.preventDefault();
        this.form.post(getRoute("account.denied.resubmit"));
    };
}
