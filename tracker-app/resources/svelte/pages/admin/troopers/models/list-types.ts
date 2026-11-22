import type { Option, PaginatedResource } from "$lib/domains/types.svelte";

export type TrooperRow = {
    id: number;
    display_name: string;
    legal_name: string;
    email: string | null;
    is_active: boolean;
    is_invalid: boolean;
    is_departed: boolean;
    role: { value: string; label: string };
    status: string;
    last_seen: string | null;
    is_long_gone: boolean;
};

export type TrooperListFilters = {
    search_term: string;
    membership_role: string | null;
    organization_id: number | null;
    sort_col: string;
    sort_dir: "asc" | "desc";
};

export type ListTroopersPageData = {
    troopers: PaginatedResource<TrooperRow>;
    filters: TrooperListFilters;
    organization: { id: number; name: string } | null;
    sort_columns: { key: string; label: string }[];
    roles: { value: string; label: string }[];
    organization_options: Option[];
};

export type RecruitPageData = {
    organization_options: Option[];
    identifiers: Record<number, { label: string; is_required: boolean }>;
};
