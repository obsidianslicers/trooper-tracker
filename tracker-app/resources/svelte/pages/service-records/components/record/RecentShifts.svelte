<script lang="ts">
    import { t } from "$lib/i18n";
    import type { RecentShift } from "../../models/record-types";
    import type { ShiftsLayout } from "../../models/vms/ServiceRecordViewModel.svelte";
    import LayoutToggle from "./LayoutToggle.svelte";
    import ShiftCard from "./ShiftCard.svelte";
    import ShiftFlags from "./ShiftFlags.svelte";

    interface Props {
        shifts: RecentShift[];
        layout: ShiftsLayout;
        ontoggle: () => void;
    }
    let { shifts, layout, ontoggle }: Props = $props();
</script>

{#snippet costume(shift: RecentShift)}
    {#if shift.costume}
        <strong>{shift.costume}</strong>
    {:else if shift.is_handler}
        {t("Handler")}
    {:else}
        <span class="text-muted">{t("N/A")}</span>
    {/if}
    {#if shift.credited_to.length > 0}
        <div class="mt-1 credited">
            <small class="text-muted d-block">{t("Credited To")}</small>
            {#each shift.credited_to as name (name)}
                <span class="badge bg-secondary me-1">{name}</span>
            {/each}
        </div>
    {/if}
{/snippet}

<LayoutToggle {layout} {ontoggle} />

{#if layout === "cards"}
    <div class="row g-3">
        {#each shifts as shift (shift.id)}
            <ShiftCard {shift}>
                {#snippet badges()}
                    <ShiftFlags {shift} cls="d-inline-block mt-1" />
                {/snippet}
                {#snippet footer()}
                    <small class="text-muted d-block">{t("Attended Costume")}</small>
                    {@render costume(shift)}
                {/snippet}
            </ShiftCard>
        {:else}
            <div class="col-12">
                <div class="alert alert-info mb-0">{t("No Historical Shifts ... Yet!")}</div>
            </div>
        {/each}
    </div>
{:else}
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th></th>
                    <th>{t("Recent Shifts")}</th>
                    <th class="text-center">{t("Date")}</th>
                    <th>{t("Attended Costume")}</th>
                </tr>
            </thead>
            <tbody>
                {#each shifts as shift (shift.id)}
                    <tr>
                        <td><img src={shift.logo_url} alt={t("Logo")} width="32" height="32" /></td>
                        <td>
                            <a href={shift.event_url}>{shift.event_name}</a>
                            <ShiftFlags {shift} cls="ms-1" />
                        </td>
                        <td class="text-start text-nowrap">
                            {shift.date}
                            <br />
                            <span class="text-muted">{shift.time}</span>
                        </td>
                        <td class="text-start text-nowrap">{@render costume(shift)}</td>
                    </tr>
                {:else}
                    <tr>
                        <td colspan="4" class="text-center py-4">{t("No Recent Shifts ... Yet!")}</td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
{/if}

<style>
    .credited {
        white-space: normal;
    }
</style>
