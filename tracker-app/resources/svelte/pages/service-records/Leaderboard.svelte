<!--
    The leaderboard: the top troopers, club activity and the most deployed costumes, over a
    period and in a club. Picking a costume opens its rankings in the panel at the bottom.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import { tick } from "svelte";
    import BarItem from "./components/BarItem.svelte";
    import CostumeSearch from "./components/CostumeSearch.svelte";
    import CostumeStats from "./components/CostumeStats.svelte";
    import LeaderboardFilters from "./components/LeaderboardFilters.svelte";
    import TrooperRanking from "./components/TrooperRanking.svelte";
    import { LeaderboardViewModel, type LeaderboardPageData } from "./models";

    const page = usePage<LeaderboardPageData>();

    pageState.title = t("Leaderboard");

    const filters = $derived(page.props.filters);
    const vm = $derived(new LeaderboardViewModel(page.props.filters));
    const activityMax = $derived(Math.max(...page.props.organization_activity.map((o) => o.count), 1));
    const costumeMax = $derived(page.props.top_costumes[0]?.count ?? 1);

    let panel: HTMLElement | undefined = $state();

    function openCostume(id: number | null) {
        if (id === null) {
            return;
        }
        vm.openCostume(id, async () => {
            await tick();
            panel?.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    }
</script>

<div class="container-fluid py-4">
    <div class="row g-4">
        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-end mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <h2 class="h4 mb-0 text-uppercase fw-bold">
                    <i class="fa-brands fa-empire me-2"></i>
                    {t("Leaderboard")}
                </h2>
                {#if filters.organization}
                    <div class="d-flex align-items-center gap-2">
                        <img src={filters.organization.logo_url} alt={t("Logo")} width="64" height="64" />
                        <div class="small text-uppercase fw-bold lh-sm">{filters.organization.name}</div>
                    </div>
                {/if}
            </div>

            <LeaderboardFilters {filters} route="service-records.leaderboard" />
        </div>

        <div class="col-12">
            <TrooperRanking
                title={t("Top 30 Troopers")}
                troopers={page.props.operatives}
                empty={t("No troopers found for the selected leaderboard filters.")}
            />
        </div>

        <div class="col-xl-6 col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-chart-pie me-2"></i>
                    {t("Organization Activity")}
                </div>
                <div class="card-body">
                    {#each page.props.organization_activity as organization (organization.name)}
                        <div class="mb-3">
                            <BarItem
                                label={organization.name}
                                count={organization.count}
                                unit={t("EVENTS")}
                                max={activityMax}
                            />
                        </div>
                    {:else}
                        <p class="text-muted mb-0">{t("No organization activity found.")}</p>
                    {/each}
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-shield-halved me-2"></i>
                    {t("Top 5 Deployed Costumes")}
                    <span class="text-muted fw-normal ms-1 hint">{t("— click to view rankings")}</span>
                </div>
                <div class="card-body">
                    {#each page.props.top_costumes as costume (costume.id ?? costume.name)}
                        {#if costume.id !== null}
                            <a
                                href={getRoute("service-records.costume", { costume: costume.id })}
                                class="d-block mb-4 text-reset text-decoration-none"
                                onclick={(e) => {
                                    e.preventDefault();
                                    openCostume(costume.id);
                                }}
                            >
                                <BarItem label={costume.name} count={costume.count} unit={t("DEPLOYMENTS")} max={costumeMax} />
                            </a>
                        {:else}
                            <div class="mb-4">
                                <BarItem label={costume.name} count={costume.count} unit={t("DEPLOYMENTS")} max={costumeMax} />
                            </div>
                        {/if}
                    {:else}
                        <p class="text-muted mb-0">{t("No costume deployments found.")}</p>
                    {/each}
                </div>
            </div>
        </div>

        <div class="col-12">
            <CostumeSearch costumes={page.props.costumes} onpick={(costume) => openCostume(costume.id)} />
        </div>

        <div class="col-12" id="costume-stats-panel" bind:this={panel} aria-busy={vm.loading}>
            {#if page.props.costume}
                <CostumeStats costume={page.props.costume} />
            {/if}
        </div>
    </div>
</div>

<style>
    .hint {
        font-size: 0.7rem;
    }
</style>
