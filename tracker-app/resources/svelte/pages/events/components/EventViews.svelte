<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";

    type View = "list" | "calendar" | "map" | "closed" | "cancelled";

    let { active, has_map = false }: { active: View; has_map: boolean } = $props();

    const views = $derived(
        [
            { key: "list", route: "events.list", icon: "fa-list", label: t("List") },
            { key: "calendar", route: "events.calendar", icon: "fa-calendar-days", label: t("Calendar") },
            { key: "map", route: "events.map", icon: "fa-map", label: t("Map") },
            { key: "closed", route: "events.closed", icon: "fa-circle-check", label: t("Completed") },
            { key: "cancelled", route: "events.cancelled", icon: "fa-ban", label: t("Cancelled") },
        ].filter((view) => view.key !== "map" || has_map),
    );
</script>

<nav class="d-flex flex-wrap justify-content-center gap-2 mb-3 event-views" aria-label={t("Event views")}>
    {#each views as view (view.key)}
        {#if view.key === active}
            <span class="btn btn-sm btn-primary active" aria-current="page">
                <i class="fa fa-fw {view.icon} me-1"></i>{view.label}
            </span>
        {:else}
            <a class="btn btn-sm btn-outline-light" href={getRoute(view.route)}>
                <i class="fa fa-fw {view.icon} me-1"></i>{view.label}
            </a>
        {/if}
    {/each}
</nav>
