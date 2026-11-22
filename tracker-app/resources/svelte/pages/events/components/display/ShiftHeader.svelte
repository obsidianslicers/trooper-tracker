<!-- The shift's status when it isn't simply open, how full it is, and its stations. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { DisplayShift } from "../../models";

    let { shift }: { shift: DisplayShift } = $props();

    const fullness = $derived(shift.fullness);
</script>

<div class="shift-header">
    <span>{shift.status_label ?? ""}</span>
    {#if fullness}
        {#if fullness.spots_left === 0}
            <span class="text-warning">
                {t("Full")}{#if fullness.stand_by > 0}
                    · {t(":count on stand-by|:count on stand-by", { count: fullness.stand_by }, fullness.stand_by)}{/if}
            </span>
        {:else}
            <span class="text-muted">
                {#if fullness.spots_left !== null && fullness.limit !== null}
                    <!-- counted the way the limit counts: troopers going, not handlers or tentative -->
                    {t(":count of :limit going", { count: fullness.limit - fullness.spots_left, limit: fullness.limit })}
                {:else}
                    {t(":count going|:count going", { count: fullness.going }, fullness.going)}
                {/if}{#if fullness.tentative > 0}
                    · {t(":count tentative|:count tentative", { count: fullness.tentative }, fullness.tentative)}{/if}
            </span>
        {/if}
    {/if}
</div>

{#if shift.stations.length > 0}
    <div class="row mb-3">
        <div class="col-12">
            <div class="station-summary">
                {#each shift.stations as station (station.id)}
                    {@const is_full = station.limit !== null && station.going >= station.limit}
                    <div class={[
                        "station-summary__item",
                        is_full ? "station-summary__item--full"
                            : station.going === 0 ? "station-summary__item--empty" : "station-summary__item--open",
                    ]}>
                        <span class="station-summary__name">{station.name}</span>
                        <span class="station-summary__count">
                            {station.going}/{station.limit ?? ""}
                            {#if is_full}<i class="fa fa-fw fa-check-circle ms-1"></i>{/if}
                        </span>
                    </div>
                {/each}
            </div>
        </div>
    </div>
{/if}
