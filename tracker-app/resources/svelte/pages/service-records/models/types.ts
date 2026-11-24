import type { Option } from "$lib/domains/types.svelte";

/** A ranked trooper (LeaderboardTrooperResource). */
export type LeaderboardTrooper = {
    id: number;
    display_name: string;
    legal_name: string | null;
    url: string;
    troop_count: number;
};

/** The period and club picked, and the clubs to pick from (LeaderboardFiltersResource). */
export type LeaderboardFilters = {
    days: number | null;
    day_options: number[];
    organization_id: number | null;
    organization: { name: string; logo_url: string } | null;
    organizations: Option[];
};

/** A club's event count (OrganizationActivityResource). */
export type OrganizationActivity = { name: string; count: number };

/** One of the top deployed costumes (DeployedCostumeResource). */
export type DeployedCostume = { id: number | null; name: string; count: number };

/** A costume the lookup can find (CostumeOptionResource). */
export type CostumeOption = { id: number; name: string };

/** A costume's numbers and top troopers (CostumeStatsResource). */
export type CostumeStats = {
    id: number;
    name: string;
    total_deployments: string;
    unique_troopers: string;
    last_deployed: string | null;
    top_troopers: LeaderboardTrooper[];
};

/** The leaderboard page (LeaderboardPageData). */
export type LeaderboardPageData = {
    filters: LeaderboardFilters;
    operatives: LeaderboardTrooper[];
    organization_activity: OrganizationActivity[];
    top_costumes: DeployedCostume[];
    costumes: CostumeOption[];
    costume: CostumeStats | null;
};

/** A costume's own page (CostumeLeaderboardPageData). */
export type CostumeLeaderboardPageData = {
    filters: LeaderboardFilters;
    costume: CostumeStats;
};
