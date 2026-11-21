<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";
    import type { EventView } from "../models";

    interface Props {
        active: EventView;
        has_map: boolean;
    }

    let { active, has_map = false }: Props = $props();

    let open = $state(false);
    let menu: HTMLElement | undefined = $state();

    const views = $derived(
        [
            { key: "list", route: "events.list", icon: "fa-list", label: t("List") },
            { key: "calendar", route: "events.calendar", icon: "fa-calendar-days", label: t("Calendar") },
            { key: "map", route: "events.map", icon: "fa-map", label: t("Map") },
            { key: "closed", route: "events.closed", icon: "fa-circle-check", label: t("Completed") },
            { key: "cancelled", route: "events.cancelled", icon: "fa-ban", label: t("Cancelled") },
        ].filter((view) => view.key !== "map" || has_map),
    );

    const current = $derived(views.find((view) => view.key === active) ?? views[0]);

    function closeOutside(event: MouseEvent) {
        if (open && menu && !menu.contains(event.target as Node)) {
            open = false;
        }
    }
</script>

<svelte:window onclick={closeOutside} />

<div class="dropdown event-views-menu" bind:this={menu}>
    <button
        type="button"
        class="btn btn-outline-light dropdown-toggle text-nowrap h-100"
        aria-haspopup="true"
        aria-expanded={open}
        aria-label={t("Event views")}
        onclick={() => (open = !open)}
    >
        <i class="fa fa-fw {current.icon} me-1"></i>{current.label}
    </button>
    <ul class={["dropdown-menu", { show: open }]}>
        {#each views as view (view.key)}
            <li>
                <a
                    class={["dropdown-item", { active: view.key === active }]}
                    href={getRoute(view.route)}
                    aria-current={view.key === active ? "page" : undefined}
                >
                    <i class="fa fa-fw {view.icon} me-2"></i>{view.label}
                </a>
            </li>
        {/each}
    </ul>
</div>
