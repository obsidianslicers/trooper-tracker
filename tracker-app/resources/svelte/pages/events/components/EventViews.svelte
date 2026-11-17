<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";

    let { has_map = false }: { has_map: boolean } = $props();
</script>

<nav class="d-flex flex-wrap justify-content-center gap-2 mb-3" aria-label={t("Event views")}>
    <span class="btn btn-sm btn-primary active" aria-current="page">
        <i class="fa fa-fw fa-list me-1"></i>{t("List")}
    </span>
    <a class="btn btn-sm btn-outline-light" href={getRoute("events.calendar")}>
        <i class="fa fa-fw fa-calendar-days me-1"></i>{t("Calendar")}
    </a>
    {#if has_map}
        <a class="btn btn-sm btn-outline-light" href={getRoute("events.map")}>
            <i class="fa fa-fw fa-map me-1"></i>{t("Map")}
        </a>
    {/if}
    <a class="btn btn-sm btn-outline-light" href={getRoute("events.closed")}>
        <i class="fa fa-fw fa-circle-check me-1"></i>{t("Completed")}
    </a>
    <a class="btn btn-sm btn-outline-light" href={getRoute("events.cancelled")}>
        <i class="fa fa-fw fa-ban me-1"></i>{t("Cancelled")}
    </a>
</nav>
