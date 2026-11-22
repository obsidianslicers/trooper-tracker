import type { ITrooperStamps, Option, PaginatedResource } from "$lib/domains/types.svelte";

export type AwardRow = {
    id: number;
    name: string;
    organization: { id: number; name: string } | null;
    logo_url: string;
    troopers_count: string;
    can_update: boolean;
};

export type ListPageData = {
    awards: PaginatedResource<AwardRow>;
    search_term: string;
    organization: { id: number; name: string } | null;
};

export type Award = {
    id: number | null;
    organization_id: number | null;
    organization_name: string | null;
    name: string;
    frequency: string;
    frequency_label: string;
    trooper_stamps?: ITrooperStamps;
};

export interface IAwardForm {
    organization_id: number | null;
    frequency: string;
    name: string;
}

export type CreatePageData = {
    award: Award;
    frequency_options: Option[];
    organization_options: Option[];
};

export type UpdatePageData = {
    award: Award;
};

export type AwardedTrooper = {
    award_trooper_id: number;
    display_name: string;
    award_date: string;
};

export type TroopersPageData = {
    award: Award;
    troopers: AwardedTrooper[];
};

export interface IAssignTrooperForm {
    trooper_id: number | string | null;
    award_date: string;
}

export type AssignTrooperPageData = {
    award: Award;
    award_date: string;
};
