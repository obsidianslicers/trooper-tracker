<script lang="ts">
    import { t } from "$lib/i18n";
    import type { UpcomingShift } from "../../models/record-types";
    import type { ShiftsLayout } from "../../models/vms/ServiceRecordViewModel.svelte";
    import LayoutToggle from "./LayoutToggle.svelte";
    import ShiftCard from "./ShiftCard.svelte";

    interface Props {
        shifts: UpcomingShift[];
        layout: ShiftsLayout;
        ontoggle: () => void;
    }
    let { shifts, layout, ontoggle }: Props = $props();
</script>

{#snippet costume(shift: UpcomingShift)}
    {#if shift.costume}
        <strong>{shift.costume}</strong>
        <br />
        <i class="small text-muted">{shift.costume_organizations}</i>
    {:else if shift.is_handler}
        {t("Handler")}
    {/if}
{/snippet}

<LayoutToggle {layout} {ontoggle} />

{#if layout === "cards"}
    <div class="row g-3">
        {#each shifts as shift (shift.id)}
            <ShiftCard {shift}>
                {#snippet footer()}
                    <small class="text-muted d-block">{t("Planned Costume")}</small>
                    {@render costume(shift)}
                {/snippet}
            </ShiftCard>
        {:else}
            <div class="col-12">
                <div class="alert alert-info mb-0">{t("No Upcoming Shifts ... Yet!")}</div>
            </div>
        {/each}
    </div>
{:else}
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th></th>
                    <th>{t("Upcoming Shifts")}</th>
                    <th class="text-center">{t("Date")}</th>
                    <th>{t("Planned Costume")}</th>
                </tr>
            </thead>
            <tbody>
                {#each shifts as shift (shift.id)}
                    <tr>
                        <td><img src={shift.logo_url} alt={t("Logo")} width="32" height="32" /></td>
                        <td><a href={shift.event_url}>{shift.event_name}</a></td>
                        <td class="text-start text-nowrap">
                            {shift.date}
                            <br />
                            <span class="text-muted">{shift.time}</span>
                        </td>
                        <td class="text-start text-nowrap">{@render costume(shift)}</td>
                    </tr>
                {:else}
                    <tr>
                        <td colspan="4" class="text-center py-4">{t("No Upcoming Shifts ... Yet!")}</td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
{/if}
