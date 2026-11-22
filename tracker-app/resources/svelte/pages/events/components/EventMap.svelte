<!--
    The Google map. Google's script comes from the page head (partials/head.blade.php) when a
    Maps key is set; the pins themselves are drawn by EventMapPins.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { EventMapPins, hasGoogleMaps, type MapViewModel } from "../models";

    let { vm }: { vm: MapViewModel } = $props();

    let element: HTMLElement | undefined = $state();
    let pins: EventMapPins | null = $state(null);

    const has_google = hasGoogleMaps();

    $effect(() => {
        if (element && has_google && !pins) {
            pins = new EventMapPins(element);
        }
    });

    $effect(() => {
        pins?.showEvents(vm.filtered);
    });

    $effect(() => {
        if (pins && vm.user_position) {
            pins.showUser(vm.user_position);
        }
    });
</script>

{#if has_google}
    <div class="event-map rounded" bind:this={element} style="height: 600px;"></div>
{:else}
    <div class="text-center text-muted py-5 border rounded">
        <i class="fa fa-fw fa-map fa-3x mb-3"></i>
        <p class="lead mb-0">{t("The map isn't available right now.")}</p>
    </div>
{/if}
