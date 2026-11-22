<!--
    One event on the Donation Event Summary: what each shift raised, and the event's total.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { DonationEventRow } from "../models/summaries";

    interface Props {
        event: DonationEventRow;
    }
    let { event }: Props = $props();
</script>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <a href={event.url} class="fw-semibold text-decoration-none">{event.name}</a>
            {#if event.organization_name}
                <small class="text-muted ms-2">{event.organization_name}</small>
            {/if}
        </div>
        <div class="text-muted text-nowrap ms-3 small">{event.date}</div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{t("Shift")}</th>
                    <th>{t("Charity")}</th>
                    <th class="text-end">{t("Direct")}</th>
                    <th class="text-end">{t("Indirect")}</th>
                    <th class="text-end">{t("Total")}</th>
                    <th class="text-end">{t("Hours")}</th>
                    <th class="text-end">{t("Attendees")}</th>
                    <th class="text-end">{t("Trooper Hrs")}</th>
                </tr>
            </thead>
            <tbody>
                {#each event.shifts as shift (shift.id)}
                    <tr>
                        <td class="text-nowrap"><small>{shift.time}</small></td>
                        <td>
                            {shift.charity_name ?? ""}
                            {#if shift.charity_notes}
                                <small class="d-block text-muted">{shift.charity_notes}</small>
                            {/if}
                        </td>
                        <td class="text-end">{shift.direct}</td>
                        <td class="text-end">{shift.indirect}</td>
                        <td class="text-end">{shift.total}</td>
                        <td class="text-end">{shift.hours}</td>
                        <td class="text-end">{shift.attendees}</td>
                        <td class="text-end">{shift.trooper_hours}</td>
                    </tr>
                {:else}
                    <tr>
                        <td colspan="8" class="text-center text-muted fst-italic">
                            {t("No shifts recorded.")}
                        </td>
                    </tr>
                {/each}
            </tbody>
            {#if event.totals}
                <tfoot>
                    <tr class="fw-semibold table-light">
                        <td colspan="2">{t("Event total")}</td>
                        <td class="text-end">{event.totals.direct}</td>
                        <td class="text-end">{event.totals.indirect}</td>
                        <td class="text-end">{event.totals.total}</td>
                        <td class="text-end">{event.totals.hours}</td>
                        <td class="text-end">{event.totals.attendees}</td>
                        <td class="text-end">{event.totals.trooper_hours}</td>
                    </tr>
                </tfoot>
            {/if}
        </table>
    </div>
</div>
