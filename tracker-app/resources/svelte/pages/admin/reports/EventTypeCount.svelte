<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import type { EventTypeCountPageData } from "./models/types";

    const page = usePage<EventTypeCountPageData>();

    pageState.title = t("Event Type Counts");

    let days = $derived(page.props.days);
</script>

<div class="table-responsive">
    <table class="table caption-top">
        <caption>{t("Count of Event Types over the last :days days.", { days })}</caption>
        <thead>
            <tr>
                <th scope="col">{t("Event Type")}</th>
                <th scope="col" class="text-end">{t("Event Count")}</th>
                <th scope="col" class="text-end">{t("Unique Trooper Count")}</th>
                <th scope="col" class="text-end">{t("Total Trooper Attendance")}</th>
            </tr>
        </thead>
        <tbody>
            {#each page.props.event_types as type (type.id)}
                <tr>
                    <td class="text-nowrap"><a href={type.events_url}>{type.name}</a></td>
                    <td class="text-end">{type.count}</td>
                    <td class="text-end">{type.unique_trooper_count}</td>
                    <td class="text-end">{type.total_trooper_count}</td>
                </tr>
            {:else}
                <tr>
                    <td colspan="4" class="text-center py-4">
                        {t("There are not any events that have been closed in the last :days days.", { days })}
                    </td>
                </tr>
            {/each}
        </tbody>
        <tfoot>
            <tr>
                <th scope="row">{t("Total")}</th>
                <th class="text-end">{page.props.totals.count}</th>
                <th class="text-end">{page.props.totals.unique_trooper_count}</th>
                <th class="text-end">{page.props.totals.total_trooper_count}</th>
            </tr>
        </tfoot>
    </table>
</div>
