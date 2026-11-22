<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import type { StatusChangeLogPageData } from "./models/types";

    const page = usePage<StatusChangeLogPageData>();

    pageState.title = t("Status Change Log");
</script>

<div class="table-responsive">
    <table class="table caption-top">
        <caption>{page.props.caption}</caption>
        <thead>
            <tr>
                <th scope="col">{t("Date/Time")}</th>
                <th scope="col">{t("Event")}</th>
                <th scope="col">{t("Shift")}</th>
                <th scope="col">{t("Trooper")}</th>
                <th scope="col">{t("Updated By")}</th>
            </tr>
        </thead>
        <tbody>
            {#each page.props.changes as change (change.id)}
                <tr>
                    <td class="text-nowrap">{change.changed_at}</td>
                    <td><a href={change.event_url}>{change.event_name}</a></td>
                    <td>{change.shift}</td>
                    <td><a href={change.trooper_url}>{change.trooper_name}</a></td>
                    <td>{change.updated_by}</td>
                </tr>
            {:else}
                <tr>
                    <td colspan="5" class="text-center py-4">
                        {t("No status changes found that were updated by anyone but the trooper.")}
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>
