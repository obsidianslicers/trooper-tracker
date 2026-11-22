<!--
    What every event on this page of the Donation Event Summary raised together.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { DonationAmounts } from "../models/summaries";

    interface Props {
        totals: DonationAmounts;
    }
    let { totals }: Props = $props();

    let stats = $derived([
        { label: t("Direct"), value: totals.direct },
        { label: t("Indirect"), value: totals.indirect },
        { label: t("Total Funds"), value: totals.total },
        { label: t("Hours"), value: totals.hours },
        { label: t("Attendees"), value: totals.attendees },
        { label: t("Trooper Hrs"), value: totals.trooper_hours },
    ]);
</script>

<div class="card">
    <div class="card-header fw-semibold">{t("Grand Total")}</div>
    <div class="card-body">
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-6 g-3 text-center">
            {#each stats as stat (stat.label)}
                <div class="col">
                    <div class="text-muted small">{stat.label}</div>
                    <div class="fw-semibold">{stat.value}</div>
                </div>
            {/each}
        </div>
    </div>
</div>
