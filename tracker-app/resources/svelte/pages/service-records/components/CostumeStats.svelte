<!--
    A costume's deployment numbers and its top troopers: the leaderboard's costume panel and the
    costume's own page.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { CostumeStats } from "../models";
    import TrooperRanking from "./TrooperRanking.svelte";

    let { costume }: { costume: CostumeStats } = $props();
</script>

<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card shadow-sm border-0 text-center py-3">
            <div class="h3 fw-bold text-primary mb-0">{costume.total_deployments}</div>
            <div class="text-uppercase text-muted small fw-bold">{t("Total Deployments")}</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card shadow-sm border-0 text-center py-3">
            <div class="h3 fw-bold text-info mb-0">{costume.unique_troopers}</div>
            <div class="text-uppercase text-muted small fw-bold">{t("Unique Troopers")}</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card shadow-sm border-0 text-center py-3">
            <div class="h3 fw-bold text-success mb-0">{costume.last_deployed ?? "—"}</div>
            <div class="text-uppercase text-muted small fw-bold">{t("Last Deployed")}</div>
        </div>
    </div>
</div>

<TrooperRanking
    title={t("Top Troopers — :costume", { costume: costume.name })}
    troopers={costume.top_troopers}
    empty={t("No trooper data found for the selected filters.")}
/>
