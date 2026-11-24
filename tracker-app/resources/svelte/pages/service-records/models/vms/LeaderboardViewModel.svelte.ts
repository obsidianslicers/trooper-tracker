import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { LeaderboardFilters } from "../types";

/**
 * The leaderboard page: opening a costume's rankings in the panel below, without counting the
 * rest of the leaderboard again.
 */
export class LeaderboardViewModel {
    loading: boolean = $state(false);

    constructor(private readonly filters: LeaderboardFilters) {}

    openCostume = (costume_id: number, onloaded?: () => void) => {
        const query: Record<string, number> = { costume_id };

        if (this.filters.days !== null) {
            query.days = this.filters.days;
        }
        if (this.filters.organization_id !== null) {
            query.organization_id = this.filters.organization_id;
        }

        this.loading = true;

        router.get(getRoute("service-records.leaderboard", query), {}, {
            only: ["costume"],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => (this.loading = false),
            onSuccess: () => onloaded?.(),
        });
    };
}
