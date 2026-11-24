<!--
    A costume's own leaderboard: its numbers and top troopers, over a period and in a club.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import CostumeStats from "./components/CostumeStats.svelte";
    import LeaderboardFilters from "./components/LeaderboardFilters.svelte";
    import type { CostumeLeaderboardPageData } from "./models";

    const page = usePage<CostumeLeaderboardPageData>();

    const filters = $derived(page.props.filters);
    const costume = $derived(page.props.costume);

    pageState.title = t(":costume — Costume Leaderboard", { costume: page.props.costume.name });

    const back = $derived.by(() => {
        const query: Record<string, number> = {};
        if (filters.days !== null) query.days = filters.days;
        if (filters.organization_id !== null) query.organization_id = filters.organization_id;
        return getRoute("service-records.leaderboard", query);
    });
</script>

<div class="container-fluid py-4">
    <div class="row g-4">
        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-end mb-2">
            <div>
                <a href={back} class="text-muted text-decoration-none small text-uppercase fw-bold">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    {t("Leaderboard")}
                </a>
                <h2 class="h4 mb-0 text-uppercase fw-bold mt-1">
                    <i class="fa-solid fa-shield-halved me-2"></i>
                    {costume.name}
                </h2>
            </div>

            <LeaderboardFilters {filters} route="service-records.costume" params={{ costume: costume.id }} />
        </div>

        <div class="col-12">
            <CostumeStats {costume} />
        </div>
    </div>
</div>
