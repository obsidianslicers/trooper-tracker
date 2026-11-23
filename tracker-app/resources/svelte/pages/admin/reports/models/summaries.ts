import type { PaginatedResource } from "$lib/domains/types.svelte";

/** Numbers arrive formatted for the reader's language; "-" means none. */

export type SortLink = { url: string; arrow: string };

/** A club the report can be narrowed to. */
export type ClubOption = { value: number; label: string };

/** A column the report can be sorted by. */
export type SortOption = { value: string; label: string };

type SummaryPage<F, S extends string> = {
    filters: F;
    caption: string;
    sorts: Record<S, SortLink>;
    csv_url: string;
};

export type ClubFilters = {
    organization_id: number | null;
    date_start: string | null;
    date_end: string | null;
};

export type TrooperEventSummaryRow = {
    id: number;
    display_name: string;
    profile_url: string;
    events_count: string;
    event_shifts_count: string;
};

export type TrooperEventSummaryPageData = SummaryPage<
    ClubFilters & { active_only: boolean },
    "display_name" | "events_count" | "event_shifts_count"
> & {
    organizations: ClubOption[];
    troopers: PaginatedResource<TrooperEventSummaryRow>;
};

export type CostumeEventSummaryRow = {
    id: number;
    name: string;
    events_count: string;
    uses_count: string;
};

export type CostumeEventSummaryPageData = SummaryPage<
    ClubFilters,
    "name" | "events_count" | "uses_count"
> & {
    organizations: ClubOption[];
    costumes: PaginatedResource<CostumeEventSummaryRow>;
    totals: { events_count: string; uses_count: string } | null;
};

export type DonationAmounts = {
    direct: string;
    indirect: string;
    total: string;
    hours: string;
    attendees: string;
    trooper_hours: string;
};

export type DonationShift = DonationAmounts & {
    id: number;
    time: string;
    charity_name: string | null;
    charity_notes: string | null;
};

export type DonationEventRow = {
    id: number;
    name: string;
    url: string;
    organization_name: string | null;
    date: string | null;
    shifts: DonationShift[];
    totals: DonationAmounts | null;
};

export type DonationFilters = {
    date_start: string | null;
    date_end: string | null;
    charity_only: boolean;
    organization_ids: number[];
    sort: string;
    dir: string;
};

export type DonationEventSummaryPageData = {
    filters: DonationFilters;
    clubs: ClubOption[];
    sort_options: SortOption[];
    caption: string | null;
    csv_url: string;
    events: PaginatedResource<DonationEventRow>;
    grand_totals: DonationAmounts | null;
};
