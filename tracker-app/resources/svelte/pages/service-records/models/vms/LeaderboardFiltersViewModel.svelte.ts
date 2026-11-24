import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { LeaderboardFilters } from "../types";

type FilterRoute = "service-records.leaderboard" | "service-records.costume";

/**
 * The club picker and the period buttons on both leaderboards. Each keeps the other filter.
 */
export class LeaderboardFiltersViewModel {
    constructor(
        private readonly filters: LeaderboardFilters,
        private readonly route_name: FilterRoute,
        private readonly route_params: Record<string, number> = {},
    ) {}

    get days(): number | null {
        return this.filters.days;
    }

    get dayOptions(): number[] {
        return this.filters.day_options;
    }

    get organizationId(): string {
        return this.filters.organization_id === null ? "" : String(this.filters.organization_id);
    }

    url = (days: number | null, organization_id: number | null = this.filters.organization_id) => {
        const query: Record<string, number> = {};

        if (days !== null) {
            query.days = days;
        }
        if (organization_id !== null) {
            query.organization_id = organization_id;
        }

        return getRoute(this.route_name, { ...this.route_params, ...query });
    };

    pickClub = (value: string) => {
        router.get(this.url(this.filters.days, value === "" ? null : Number(value)));
    };
}
