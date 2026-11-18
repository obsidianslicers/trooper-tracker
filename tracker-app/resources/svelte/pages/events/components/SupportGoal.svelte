<script lang="ts">
    import { t } from "$lib/i18n";
    import type { SupportGoal } from "../models";

    let { support }: { support: SupportGoal } = $props();

    const progress = $derived(Math.max(0, Math.min(100, Math.round(support.progress))));
    const goal = $derived(
        new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            .format(support.goal),
    );
</script>

<div class="card mb-3 support-goal">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h5 class="mb-0">
                🎯 {t("This Month's Support Goal")}
                <span class="text-muted">- ${goal}</span>
            </h5>
            {#if support.url}
                <a href={support.url} target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">
                    {t("❤️ Support the Garrison")}
                    <i class="fa fa-fw fa-external-link"></i>
                </a>
            {/if}
        </div>
        <div
            class="progress mb-2"
            style="height: 1.25rem;"
            role="progressbar"
            aria-valuenow={progress}
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label={t("This Month's Support Goal")}
        >
            <div class="progress-bar bg-success" style="width: {progress}%;">{progress}%</div>
        </div>
        <p class="mb-0 small text-muted d-none d-md-block">{@html support.message}</p>
    </div>
</div>
