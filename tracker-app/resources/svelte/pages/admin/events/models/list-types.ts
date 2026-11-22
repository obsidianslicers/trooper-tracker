import type { PaginatedResource } from "$lib/domains/types.svelte";

export type AdminEventRow = {
    id: number;
    name: string;
    time_display: string;
    shift_count: number;
    organization: { id: number; name: string };
    logo_url: string;
    status: { value: string; label: string };
    can_update: boolean;
};

export type AdminEventFilters = {
    status: string | null;
    search_term: string;
    organization_id: number | null;
};

export type ListPageData = {
    events: PaginatedResource<AdminEventRow>;
    filters: AdminEventFilters;
    organization: { id: number; name: string } | null;
    statuses: { value: string; label: string }[];
};

export type CopyPageData = {
    event: { id: number; name: string; event_start: string | null; organization_name: string };
};
