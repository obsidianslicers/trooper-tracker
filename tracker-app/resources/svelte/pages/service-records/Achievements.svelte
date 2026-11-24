<!-- Milestones troopers reached lately: a table on wider screens, cards on phones. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import EmptyList from "./components/lists/EmptyList.svelte";
    import ListHeader from "./components/lists/ListHeader.svelte";
    import Lookback from "./components/lists/Lookback.svelte";
    import type { AchievementsPageData } from "./models/list-types";

    let { days, achievements }: AchievementsPageData = $props();

    pageState.title = t("Achievements");
</script>

<div class="container-fluid py-4">
    <ListHeader title={t("Achievements")} icon="fa-brands fa-empire">
        <Lookback {days} />
    </ListHeader>

    {#if achievements.length > 0}
        <div class="d-none d-md-block table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{t("Trooper")}</th>
                        <th>{t("Achievement")}</th>
                        <th>{t("Earned On")}</th>
                    </tr>
                </thead>
                <tbody>
                    {#each achievements as achievement (achievement.id)}
                        <tr>
                            <td><a href={achievement.trooper_url} class="fw-semibold">{achievement.trooper_name}</a></td>
                            <td>
                                <i class="fa fa-fw {achievement.icon} me-2 text-warning"></i>
                                {achievement.description}
                            </td>
                            <td class="text-nowrap">{achievement.date}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <div class="d-block d-md-none">
            <div class="row g-3">
                {#each achievements as achievement (achievement.id)}
                    <div class="col-12">
                        <div class="card bg-dark border-secondary shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0 d-flex align-items-center justify-content-center bg-secondary-subtle rounded text-warning fs-4 icon-box">
                                        <i class="fa {achievement.icon}"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0 ms-3">
                                        <small class="text-muted d-block text-uppercase fw-bold">{t("Trooper")}</small>
                                        <a href={achievement.trooper_url} class="h6 mb-0 text-decoration-none fw-bold text-truncate d-block">
                                            {achievement.trooper_name}
                                        </a>
                                    </div>
                                </div>
                                <div class="row g-2 pt-2 border-top border-secondary align-items-center">
                                    <div class="col-7">
                                        <small class="text-muted d-block text-uppercase fw-bold">{t("Achievement Earned")}</small>
                                        <span class="text-white small fw-semibold d-block text-break">{achievement.description}</span>
                                    </div>
                                    <div class="col-5 text-end">
                                        <small class="text-muted d-block text-uppercase fw-bold mb-1">{t("Date")}</small>
                                        <span class="badge bg-secondary text-nowrap">{achievement.date}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                {/each}
            </div>
        </div>
    {:else}
        <EmptyList message={t("No achievements found in the last :days days.", { days })} />
    {/if}
</div>

<style>
    .icon-box {
        width: 42px;
        height: 42px;
    }
</style>
