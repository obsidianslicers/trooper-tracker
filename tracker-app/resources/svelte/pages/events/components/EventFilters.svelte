<script lang="ts">
    import { t } from "$lib/i18n";
    import type { ListEventsViewModel } from "../models";

    let { vm }: { vm: ListEventsViewModel } = $props();
</script>

<div class="event-filters mb-2">
    <div class="row g-2 align-items-stretch">
        <div class="col-12 col-lg-5">
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
        <div class="col-12 col-md-6 col-lg-4">
            <select
                class="form-select h-100"
                aria-label={t("Requested Character Types:")}
                value={vm.costume_organization_id}
                onchange={(e) => vm.setCostumeOrganization(e.currentTarget.value)}
            >
                <option value="">{t("Any requested characters")}</option>
                {#each vm.costume_organizations as organization (organization.value)}
                    <option value={String(organization.value)}>{organization.label}</option>
                {/each}
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <button
                type="button"
                class={["btn w-100 h-100", vm.has_hosting_filter ? "btn-primary" : "btn-outline-light"]}
                onclick={() => (vm.show_hosting_picker = true)}
            >
                <i class="fa fa-fw fa-sitemap me-1"></i>
                {#if vm.has_hosting_filter}
                    {t("Hosted by :count selected", { count: vm.hosting_organization_ids.length })}
                {:else}
                    {t("Hosted by anyone")}
                {/if}
            </button>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
        <button
            type="button"
            class={["btn btn-sm rounded-pill", vm.only_mine ? "btn-success" : "btn-outline-success"]}
            aria-pressed={vm.only_mine}
            onclick={vm.toggleOnlyMine}
        >
            <i class="fa fa-fw fa-user-check me-1"></i>
            {t("My troops")}
            <span class="badge rounded-pill text-bg-light ms-1">{vm.my_event_count}</span>
        </button>
        <button
            type="button"
            class={["btn btn-sm rounded-pill", vm.needs_troopers ? "btn-danger" : "btn-outline-danger"]}
            aria-pressed={vm.needs_troopers}
            onclick={vm.toggleNeedsTroopers}
        >
            <i class="fa fa-fw fa-bullhorn me-1"></i>
            {t("Needs troopers")}
        </button>

        {#if vm.costume_organization_id}
            <span class="badge text-bg-info d-inline-flex align-items-center py-2">
                {vm.costume_organization_name}
                <button
                    type="button"
                    class="btn-close btn-close-white ms-2"
                    style="font-size: .6rem;"
                    aria-label={t("Remove requested character type filter")}
                    onclick={() => vm.setCostumeOrganization("")}
                ></button>
            </span>
        {/if}
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
                {t(":count event|:count events", {}, vm.filtered.length)}
            </span>
            <div class="form-check form-switch mb-0">
                <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    id="show-allowed-orgs"
                    checked={vm.show_allowed_orgs}
                    onchange={vm.toggleAllowedOrgs}
                />
                <label class="form-check-label small" for="show-allowed-orgs">
                    {t("Show allowed orgs")}
                </label>
            </div>
        </div>
    </div>
</div>
