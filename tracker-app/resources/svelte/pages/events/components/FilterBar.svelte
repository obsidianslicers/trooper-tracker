<script lang="ts">
    import { t } from "$lib/i18n";
    import type { Snippet } from "svelte";
    import type { EventFiltersViewModel, EventView } from "../models";
    import EventViews from "./EventViews.svelte";
    import HostingPicker from "./HostingPicker.svelte";

    interface Props {
        vm: EventFiltersViewModel;
        active: EventView;
        count: number;
        quick?: Snippet;
        end?: Snippet;
    }

    let { vm, active, count, quick, end }: Props = $props();

    //  On phones the filters fold away behind the Filters button; from md up they always show.
    const folded = $derived(vm.show_filters ? "" : "d-none d-md-flex");
</script>

{#snippet hostingChips()}
    {#each vm.active_hosting_ids as id (id)}
        <span class="badge text-bg-primary d-inline-flex align-items-center py-2">
            {vm.hostingName(id)}
            <button
                type="button"
                class="btn-close btn-close-white ms-2"
                style="font-size: .6rem;"
                aria-label={t("Remove hosting organization filter")}
                onclick={() => vm.removeHosting(id)}
            ></button>
        </span>
    {/each}
{/snippet}

{#snippet eventCount()}
    <span class="small text-muted" aria-live="polite">
        {t(":count event|:count events", {}, count)}
    </span>
{/snippet}

<div class="event-filters mb-2">
    <div class="row g-2 align-items-stretch">
        <div class="col-auto d-none d-md-block">
            <EventViews {active} has_map={vm.has_map} />
        </div>

        <div class="col-12 col-md">
            <div class="input-group">
                <span class="input-group-text"><i class="fa fa-fw fa-magnifying-glass"></i></span>
                <input
                    type="search"
                    class="form-control"
                    placeholder={t("Search by event name or venue")}
                    aria-label={t("Search events")}
                    value={vm.search_term}
                    oninput={(e) => vm.setSearch(e.currentTarget.value)}
                />
            </div>
        </div>

        <div class="col-12 d-flex gap-2 d-md-none event-filters-phone">
            <EventViews {active} has_map={vm.has_map} />
            <button
                type="button"
                class={["btn flex-grow-1", vm.filter_count > 0 || vm.show_filters ? "btn-primary" : "btn-outline-light"]}
                aria-expanded={vm.show_filters}
                aria-controls="event-filter-options"
                onclick={vm.toggleFilters}
            >
                <i class="fa fa-fw fa-sliders me-1"></i>{t("Filters")}
                {#if vm.filter_count > 0}
                    <span class="badge rounded-pill text-bg-light ms-1">{vm.filter_count}</span>
                {/if}
                <i class="fa fa-fw {vm.show_filters ? 'fa-chevron-up' : 'fa-chevron-down'} ms-1"></i>
            </button>
        </div>

        {#if !vm.show_filters}
            <div class="col-12 d-flex flex-wrap align-items-center gap-2 d-md-none">
                {@render hostingChips()}
                <span class="ms-auto">{@render eventCount()}</span>
            </div>
        {/if}

        <div class={["col-12 col-md-4", folded]} id="event-filter-options">
            <button
                type="button"
                class={["btn w-100 h-100", vm.has_hosting_filter ? "btn-primary" : "btn-outline-light"]}
                onclick={() => (vm.show_hosting_picker = true)}
            >
                <i class="fa fa-fw fa-sitemap me-1"></i>
                {#if vm.has_hosting_filter}
                    {t("Squads: :count selected", { count: vm.hosting_organization_ids.length })}
                {:else}
                    {t("Squads: All")}
                {/if}
            </button>
        </div>
    </div>

    <div class={["flex-wrap align-items-center gap-2 mt-2", folded || "d-flex"]}>
        {@render quick?.()}

        {@render hostingChips()}

        {#if vm.has_filters}
            <button type="button" class="btn btn-sm btn-link" onclick={vm.clearFilters}>
                {t("Clear Filters")}
            </button>
        {/if}

        <div class="ms-auto d-flex align-items-center gap-3">
            {@render eventCount()}
            {@render end?.()}
        </div>
    </div>
</div>

<HostingPicker {vm} />
