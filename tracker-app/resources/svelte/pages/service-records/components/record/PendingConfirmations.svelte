<!-- The trooper's own past shifts still waiting for "did you make it?". -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { PendingShift } from "../../models/record-types";

    let { shifts }: { shifts: PendingShift[] } = $props();
</script>

{#if shifts.length > 0}
    <div class="service-record-confirmation mb-3 shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fa fa-fw fa-clipboard-check service-record-confirmation-icon"></i>
            <strong class="service-record-confirmation-title">
                {t(":count shift needs confirmation|:count shifts need confirmation", {}, shifts.length)}
            </strong>
        </div>
        <div class="service-record-confirmation-list">
            {#each shifts as shift (shift.id)}
                <div class="service-record-confirmation-item">
                    <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                        <div class="flex-grow-1">
                            <a href={shift.event_url} class="service-record-confirmation-link">{shift.event_name}</a>
                            <div class="service-record-confirmation-time">{shift.date} &middot; {shift.time}</div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href={shift.made_it_url} class="btn btn-success btn-sm">{t("Made It")}</a>
                            <a href={shift.missed_it_url} class="btn btn-outline-danger btn-sm">{t("Missed It")}</a>
                        </div>
                    </div>
                </div>
            {/each}
        </div>
    </div>
{/if}
