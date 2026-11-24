import type { Option } from "$lib/domains/types.svelte";
import {
    OrganizationSelectionViewModel,
    type OrganizationSelectionInput,
    type SelectableOrganization,
} from "$lib/components/models/OrganizationSelectionViewModel.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import { AuthFactory } from "../factories";
import type { RegistrationSession } from "../types";

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
    organizations: SelectableOrganization[];
    oauth: RegistrationSession;
};

export class RegisterViewModel extends OrganizationSelectionViewModel<RegisterViewModel, Registration> {
    #useOAuthEmail = false;
    membership_roles = $state<Option[]>([]);

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

            this.form.organizations = OrganizationSelectionViewModel.blankSelections(this.organizations);

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
}
