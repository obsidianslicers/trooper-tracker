import type { ITrooperStamps, Option } from "$lib/domains/types.svelte";

export type OrganizationRow = {
    id: number;
    name: string;
    depth: number;
    logo_url: string;
    synchronized_at: string | null;
    can_manage: boolean;
    add_child_label: string | null;
    has_costumes: boolean;
};

export type ListPageData = {
    organizations: OrganizationRow[];
};

export type CreatePageData = {
    parent: { id: number; name: string };
};

export interface IOrganizationForm {
    name: string;
    synchronizer: string;
}

export type UpdatePageData = {
    organization: {
        id: number;
        name: string;
        synchronizer: string | null;
        is_club: boolean;
        parent: { name: string; label: string } | null;
        logo_url: string;
        trooper_stamps: ITrooperStamps;
    };
    synchronizer_options: Option[] | null;
    plugin_fields: string;
};

export type CostumesPageData = {
    organization: { id: number; name: string; is_club: boolean };
    costumes: { id: number; name: string; synchronized_at: string | null }[];
};
