import type { ITrooperStamps, PaginatedResource } from "$lib/domains/types.svelte";

export type CostumeRow = {
    id: number;
    name: string;
    organizations: string;
    can_delete: boolean;
};

export type ListPageData = {
    costumes: PaginatedResource<CostumeRow>;
    search_term: string;
};

export type UpdatePageData = {
    costume: {
        id: number;
        name: string;
        organization_ids: number[];
        can_delete: boolean;
        trooper_stamps: ITrooperStamps;
    };
    organizations: { id: number; name: string }[];
    deletion_impact: { event_troopers: number; trooper_costumes: number } | null;
    confirm_delete: boolean;
};

export interface ICostumeForm {
    name: string;
    organization_ids: number[];
}
