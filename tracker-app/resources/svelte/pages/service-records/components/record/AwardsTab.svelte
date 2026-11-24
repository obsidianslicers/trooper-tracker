<script lang="ts">
    import { t } from "$lib/i18n";
    import type { TrooperAward } from "../../models/record-types";

    let { awards }: { awards: TrooperAward[] } = $props();
</script>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>{t("Award")}</th>
                <th>{t("Date")}</th>
            </tr>
        </thead>
        <tbody>
            {#each awards as award (award.id)}
                <tr>
                    <td>{award.name}</td>
                    <td>{award.date ?? ""}</td>
                </tr>
            {:else}
                <tr>
                    <td colspan="2" class="text-center py-4">{t("No Awards ... Yet!")}</td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>
