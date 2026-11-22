<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventDetails } from "../../models";
    import SectionTitle from "./SectionTitle.svelte";

    let { venue }: { venue: EventDetails["venue"] } = $props();
</script>

<SectionTitle>{t("Mission Coordinates")}</SectionTitle>
<div class="row">
    <div class="col-12">{venue.name ?? ""}</div>
    {#if venue.address && venue.map_url}
        <div class="col-12">
            <a href={venue.map_url} target="_blank" rel="noopener noreferrer"
               class="text-decoration-none d-flex align-items-center">
                <span><i class="fa fa-fw fa-location-dot me-2"></i></span>
                <span class="p-1 text-white text-break d-inline-block">{venue.address}</span>
            </a>
        </div>
    {/if}
    {#if venue.website}
        <div class="col-12">
            <a href={venue.website} target="_blank" rel="noopener noreferrer"
               class="text-decoration-none d-flex align-items-center">
                <span><i class="fa fa-fw fa-globe me-2"></i></span>
                <span class="p-1 text-white text-break">{venue.website}</span>
            </a>
        </div>
    {/if}
</div>
