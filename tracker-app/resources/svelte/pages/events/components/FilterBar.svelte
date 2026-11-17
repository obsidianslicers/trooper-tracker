<script lang="ts">
    import { t } from "$lib/i18n";
    import type { Snippet } from "svelte";
    import type { EventFiltersViewModel } from "../models";
    import HostingPicker from "./HostingPicker.svelte";

    interface Props {
        vm: EventFiltersViewModel;
        count: number;
        quick?: Snippet;
        end?: Snippet;
    }

    let { vm, count, quick, end }: Props = $props();
</script>

<div class="event-filters mb-2">
    <div class="row g-2 align-items-stretch">
        <div class="col-12 col-md-8">
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
        <div class="col-12 col-md-4">
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

    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
        {@render quick?.()}

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

        {#if vm.has_filters}
            <button type="button" class="btn btn-sm btn-link" onclick={vm.clearFilters}>
                {t("Clear Filters")}
            </button>
        {/if}

        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="small text-muted" aria-live="polite">
                {t(":count event|:count events", {}, count)}
            </span>
            {@render end?.()}
        </div>
    </div>
</div>

<HostingPicker {vm} />
