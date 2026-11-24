<script lang="ts">
    import { t } from "$lib/i18n";
    import PluginSlot from "$lib/components/PluginSlot.svelte";
    import type { DonationRow } from "../../models/record-types";

    interface Props {
        donations: DonationRow[];
        total: string | null;
        slot: string;
    }
    let { donations, total, slot }: Props = $props();
</script>

<PluginSlot name="service-records.trooper.donations" html={slot} />

<h6 class="text-muted text-uppercase small fw-bold mb-2 mt-3">{t("Donation Records")}</h6>
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>{t("Date")}</th>
                <th class="text-end">{t("Amount")}</th>
            </tr>
        </thead>
        <tbody>
            {#each donations as donation (donation.id)}
                <tr>
                    <td>{donation.date ?? ""}</td>
                    <td class="text-end">$ {donation.amount}</td>
                </tr>
            {:else}
                <tr>
                    <td colspan="2" class="text-center py-4">{t("No Donations ... Yet!")}</td>
                </tr>
            {/each}
        </tbody>
        {#if donations.length > 0}
            <tfoot>
                <tr class="fw-bold">
                    <td>{t("Total")}</td>
                    <td class="text-end">$ {total}</td>
                </tr>
            </tfoot>
        {/if}
    </table>
</div>
