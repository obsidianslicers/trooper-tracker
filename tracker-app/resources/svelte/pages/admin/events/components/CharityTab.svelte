<script lang="ts">
    import { t } from "$lib/i18n";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import type { EventShiftCharity } from "../models";
    import CharityRow from "./CharityRow.svelte";

    interface Props {
        event_id: number;
        shifts: EventShiftCharity[];
    }
    let { event_id, shifts }: Props = $props();
</script>

<SlimView>
    <p class="text-muted small">{t("Changes save as you make them.")}</p>
    {#each shifts as shift, i (shift.id)}
        {#if i > 0}
            <hr class="my-3" />
        {/if}
        <CharityRow {event_id} {shift} />
    {:else}
        <p class="text-muted">{t("Add a shift to record charity numbers.")}</p>
    {/each}
</SlimView>
