<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import EventMap from "./components/EventMap.svelte";
    import FilterBar from "./components/FilterBar.svelte";
    import { MapViewModel, type MapPageData } from "./models";

    const page = usePage<MapPageData>();

    pageState.title = t("Event Map");

    const vm = new MapViewModel(page.props);
</script>

<FilterBar {vm} active="map" count={vm.filtered.length}>
    {#snippet end()}
        {#if vm.can_locate}
            <button type="button" class="btn btn-sm btn-primary" onclick={vm.locate} disabled={vm.locating}>
                <i class={["fa fa-fw fa-location-crosshairs", { "fa-beat-fade": vm.locating }]}></i>
                {t("Use Current Location")}
            </button>
        {/if}
    {/snippet}
</FilterBar>

{#if vm.location_error}
    <div class="small text-warning mb-2" role="status">{vm.location_error}</div>
{/if}

<EventMap {vm} />
