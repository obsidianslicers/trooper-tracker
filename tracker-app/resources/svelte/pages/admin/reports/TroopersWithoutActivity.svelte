<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import type { TroopersWithoutActivityPageData } from "./models/types";

    const page = usePage<TroopersWithoutActivityPageData>();

    pageState.title = t("Troopers Without Activity");
</script>

<div class="table-responsive">
    <table class="table caption-top">
        <caption>{page.props.caption}</caption>
        <thead>
            <tr>
                <th scope="col">{t("Trooper")}</th>
                <th scope="col">{t("Last Seen")}</th>
            </tr>
        </thead>
        <tbody>
            {#each page.props.troopers as trooper (trooper.id)}
                <tr>
                    <td><a href={trooper.profile_url}>{trooper.display_name}</a></td>
                    <td>{trooper.last_seen}</td>
                </tr>
            {:else}
                <tr>
                    <td colspan="2" class="text-center py-4">
                        {t("No troopers found, all present and accounted for.")}
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>
