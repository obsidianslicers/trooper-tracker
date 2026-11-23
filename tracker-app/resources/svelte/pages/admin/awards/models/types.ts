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

/** The award above the troopers awarded and award a trooper pages. */
export type AwardSummary = {
    id: number;
    name: string;
    organization_name: string | null;
    frequency_label: string;
};

/** The award on its update page. */
export type Award = AwardSummary & {
    trooper_stamps: ITrooperStamps;
};

export interface IAwardForm {
    organization_id: number | null;
    frequency: string;
    name: string;
}

export type CreatePageData = {
    /** blank, or a copy of another award */
    award: IAwardForm;
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
    award: AwardSummary;
    troopers: AwardedTrooper[];
};

export interface IAssignTrooperForm {
    trooper_id: number | string | null;
    award_date: string;
}

export type AssignTrooperPageData = {
    award: AwardSummary;
    award_date: string;
};
