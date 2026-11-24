<!-- Awards given lately: a table on wider screens, cards on phones. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import EmptyList from "./components/lists/EmptyList.svelte";
    import ListHeader from "./components/lists/ListHeader.svelte";
    import Lookback from "./components/lists/Lookback.svelte";
    import type { AwardsPageData } from "./models/list-types";

    let { days, awards }: AwardsPageData = $props();

    pageState.title = t("Awards");
</script>

<div class="container-fluid py-4">
    <ListHeader title={t("Awards")} icon="fa-brands fa-empire">
        <Lookback {days} />
    </ListHeader>

    {#if awards.length > 0}
        <div class="d-none d-md-block table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th colspan="2">{t("Organization")}</th>
                        <th>{t("Award")}</th>
                        <th>{t("Trooper")}</th>
                        <th>{t("Award Date")}</th>
                    </tr>
                </thead>
                <tbody>
                    {#each awards as award (award.id)}
                        <tr>
                            <td class="logo-col"><img src={award.logo_url} alt="" width="32" height="32" /></td>
                            <td>{award.organization}</td>
                            <td>{award.award}</td>
                            <td><a href={award.trooper_url} class="fw-semibold">{award.trooper_name}</a></td>
                            <td class="text-nowrap">{award.date}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <div class="d-block d-md-none">
            <div class="row g-3">
                {#each awards as award (award.id)}
                    <div class="col-12">
                        <div class="card bg-dark border-secondary shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0 me-3 bg-secondary-subtle p-1 rounded">
                                        <img src={award.logo_url} alt="" width="40" height="40" />
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <small class="text-muted d-block text-uppercase fw-bold">{t("Recipient")}</small>
                                        <a href={award.trooper_url} class="h6 mb-0 text-decoration-none fw-bold text-truncate d-block">
                                            {award.trooper_name}
                                        </a>
                                    </div>
                                </div>
                                <div class="row g-2 pt-2 border-top border-secondary">
                                    <div class="col-7">
                                        <small class="text-muted d-block text-uppercase fw-bold">{t("Award / Unit")}</small>
                                        <span class="text-white small fw-semibold d-block text-break">{award.award}</span>
                                        <span class="text-muted small d-block text-truncate">{award.organization}</span>
                                    </div>
                                    <div class="col-5 text-end">
                                        <small class="text-muted d-block text-uppercase fw-bold">{t("Awarded")}</small>
                                        <span class="badge bg-secondary text-nowrap">{award.date}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                {/each}
            </div>
        </div>
    {:else}
        <EmptyList message={t("No awards found in the last :days days.", { days })} />
    {/if}
</div>

<style>
    .logo-col {
        width: 40px;
    }
</style>
