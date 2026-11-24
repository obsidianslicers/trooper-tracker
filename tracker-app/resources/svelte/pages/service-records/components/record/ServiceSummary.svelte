<!--
    The record's left column: troops and hours, troops per club, rank, funds and donations, then
    the milestones earned.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import PluginSlot from "$lib/components/PluginSlot.svelte";
    import type { ServiceSummary } from "../../models/record-types";

    let { summary, slot }: { summary: ServiceSummary; slot: string } = $props();
</script>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h6 class="text-muted text-uppercase small fw-bold mb-3">{t("Service Summary")}</h6>
        <div class="row g-2">
            <div class="col-6">
                <div class="p-3 border rounded text-center">
                    <div class="h3 mb-0">{summary.total_shifts}</div>
                    <div class="small text-muted">{t("Troops/Missions")}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 border rounded text-center">
                    <div class="h3 mb-0">{summary.total_hours}</div>
                    <div class="small text-muted">{t("Mission Hours")}</div>
                </div>
            </div>
        </div>
        <PluginSlot name="service-records.trooper.summary" html={slot} />
        <table class="table table-sm mt-3 mb-0">
            <tbody>
                <tr class="small">
                    <td class="text-muted border-0">
                        <span title={t("Theme determines displayed rank")}>{t("Tracker Theme")}</span>
                    </td>
                    <td class="text-end fw-bold border-0">{summary.theme}</td>
                </tr>
                {#each summary.clubs as club (club.name)}
                    <tr class="small">
                        <td class="text-muted border-0">{club.name}</td>
                        <td class="text-end fw-bold border-0">
                            {club.troops}
                            <span class="text-muted fw-normal">{t("troops")}</span>
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
        <hr class="my-2" />
        <table class="table table-sm mt-3 mb-0">
            <tbody>
                <tr class="small">
                    <td class="text-muted border-0">{t("Trooper Position")}</td>
                    <td class="text-end fw-bold border-0">#{summary.rank}</td>
                </tr>
                <tr class="small">
                    <td class="text-muted border-0">{t("Direct Funds")}</td>
                    <td class="text-end fw-bold border-0">{summary.direct_funds}</td>
                </tr>
                <tr class="small">
                    <td class="text-muted border-0">{t("Indirect Funds")}</td>
                    <td class="text-end fw-bold border-0">{summary.indirect_funds}</td>
                </tr>
                {#if summary.total_donated}
                    <tr class="small">
                        <td class="text-muted border-0">{t("Total Donated (personal)")}</td>
                        <td class="text-end fw-bold border-0">{summary.total_donated}</td>
                    </tr>
                {/if}
                {#if summary.donation_months}
                    <tr class="small">
                        <td class="text-muted border-0">{t("Donation Months (personal)")}</td>
                        <td class="text-end fw-bold border-0">{summary.donation_months}</td>
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-dark text-white">
        <h5 class="card-title mb-0">{t("Achievements")}</h5>
    </div>
    <ul class="list-group list-group-flush">
        {#each summary.milestones as milestone (milestone.description)}
            <li class="list-group-item d-flex align-items-center">
                <span class={["badge d-inline-flex align-items-center gap-2", milestone.is_501st && "text-success"]}>
                    <i class="fa-solid fa-fw {milestone.icon}"></i>
                    {milestone.description}
                </span>
            </li>
        {/each}
    </ul>
    {#if summary.donor_image_url}
        <div class="card-body text-center pt-3 pb-2">
            <a href="https://www.fl501st.com/boards/account/upgrades" target="_blank" rel="noopener noreferrer">
                <img src={summary.donor_image_url} alt={t("Active Donor")} class="img-fluid donor" />
            </a>
        </div>
    {/if}
</div>

<style>
    .donor {
        max-height: 200px;
    }
</style>
