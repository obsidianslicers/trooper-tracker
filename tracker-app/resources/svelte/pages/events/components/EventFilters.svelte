<script lang="ts">
    import { t } from "$lib/i18n";
    import type { ListEventsViewModel } from "../models";
    import FilterBar from "./FilterBar.svelte";

    let { vm }: { vm: ListEventsViewModel } = $props();
</script>

<FilterBar {vm} count={vm.filtered.length}>
    {#snippet quick()}
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
    {/snippet}
    {#snippet end()}
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
    {/snippet}
</FilterBar>
