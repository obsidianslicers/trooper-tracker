import type { Option } from "../types.svelte";

export type Login = {
    email: string;
    password: string;
    remember_me: boolean;
};

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

export type AuthConfiguration = {
    xenforo: { name: string; required: boolean; configured: boolean };
    google: { enabled: boolean; configured: boolean };
    email_password: { enabled: boolean };
};

export type LoginPageData = {
    oauth: AuthConfiguration;
};

export type RegisterPageData = {
    membership_roles: Option[];
    organizations: Organization[];
    oauth: AuthConfiguration;
};

export type OrganizationSelectionInput = {
    selected: boolean;
    identifier: string | null;
    region_id: string | number | null;
    unit_id: string | number | null;
};

export type RegistrationInputs = {
    email: string | null;
    password: string | null;
    display_name: string | null;
    legal_name: string | null;
    account_type: "visitor" | "member" | "handler" | null;
    date_of_birth: string | null;
    guardian_email: string | null;
    /**
     * Keyed by Organization ID: Record<number, OrganizationSelectionInput>
     * e.g., { 1: { selected: true, identifier: 'TK-1234', region_id: 5, unit_id: 12 } }
     */
    organizations: Record<number, OrganizationSelectionInput>;
};