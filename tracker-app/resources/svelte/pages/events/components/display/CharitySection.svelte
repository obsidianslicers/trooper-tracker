<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventDetails } from "../../models";
    import SectionTitle from "./SectionTitle.svelte";

    let { charity }: { charity: EventDetails["charity"] } = $props();
</script>

{#if charity.length > 0}
    <div class="mt-3">
        <SectionTitle>{t("Charity")}</SectionTitle>
        {#each charity as shift, i (shift.id)}
            <div class={[i > 0 && "mt-2"]}>
                {#if charity.length > 1}
                    <p class="mb-1 text-muted small">{shift.when}</p>
                {/if}
                {#if shift.name}
                    <p class="mb-1"><strong>{shift.name}</strong></p>
                {/if}
                <p class="mb-1">{t("Direct Funds: $:amount", { amount: shift.direct_funds })}</p>
                <p class="mb-1">{t("Indirect Funds: $:amount", { amount: shift.indirect_funds })}</p>
                {#if shift.hours}
                    <p class="mb-1">{t("Hours: :hours", { hours: shift.hours })}</p>
                {/if}
                {#if shift.notes}
                    <p class="mb-1">{shift.notes}</p>
                {/if}
            </div>
        {/each}
    </div>
{/if}
