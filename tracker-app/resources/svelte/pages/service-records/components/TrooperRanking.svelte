<!--
    A ranked table of troopers with their troop counts and an activity bar.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { LeaderboardTrooper } from "../models";

    interface Props {
        title: string;
        troopers: LeaderboardTrooper[];
        empty: string;
    }
    let { title, troopers, empty }: Props = $props();

    const max = $derived(Math.max(troopers[0]?.troop_count ?? 1, 1));
</script>

<div class="card shadow-sm border-0">
    <div class="card-header bg-dark text-white text-uppercase small fw-bold">
        <i class="fa-solid fa-bolt me-2"></i>
        {title}
    </div>
    {#if troopers.length > 0}
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr class="text-uppercase small text-muted">
                        <th scope="col" class="ps-3 rank">{t("Rank")}</th>
                        <th scope="col">{t("Trooper")}</th>
                        <th scope="col" class="text-end count">{t("Troops")}</th>
                        <th scope="col" class="pe-3 activity">{t("Activity")}</th>
                    </tr>
                </thead>
                <tbody>
                    {#each troopers as trooper, index (trooper.id)}
                        <tr>
                            <td class="ps-3 text-muted fw-bold">#{index + 1}</td>
                            <td>
                                <a href={trooper.url} class="fw-bold text-uppercase text-decoration-none">
                                    {trooper.display_name}
                                </a>
                                {#if trooper.legal_name}
                                    <span class="text-muted small d-block d-sm-inline ms-sm-1">
                                        {trooper.legal_name}
                                    </span>
                                {/if}
                            </td>
                            <td class="text-end text-primary fw-bold">
                                {trooper.troop_count}
                                <small class="text-muted unit">{t("TROOPS")}</small>
                            </td>
                            <td class="pe-3">
                                <div class="progress thin">
                                    <div
                                        class="progress-bar bg-success"
                                        role="progressbar"
                                        style:width="{(trooper.troop_count / max) * 100}%"
                                    ></div>
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    {:else}
        <div class="card-body">
            <p class="text-muted mb-0">{empty}</p>
        </div>
    {/if}
</div>

<style>
    .rank {
        width: 4rem;
    }
    .count {
        width: 8rem;
    }
    .activity {
        width: 35%;
    }
    .unit {
        font-size: 0.6rem;
    }
    .thin {
        height: 5px;
    }
</style>
