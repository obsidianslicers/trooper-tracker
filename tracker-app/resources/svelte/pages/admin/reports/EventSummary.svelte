<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import type { EventSummaryPageData } from "./models/types";

    const page = usePage<EventSummaryPageData>();

    pageState.title = t("Event Volunteer Counts");

    let days = $derived(page.props.days);
    let totals = $derived(page.props.totals);
</script>

<div class="table-responsive">
    <table class="table caption-top">
        <caption>{t("Summary of Event Details over the last :days days.", { days })}</caption>
        <thead>
            <tr>
                <th style="width: 36px;"></th>
                <th></th>
                <th></th>
                <th scope="col" class="text-nowrap text-center" colspan="2">{t("-- Troopers --")}</th>
                <th scope="col" class="text-nowrap text-center" colspan="2">{t("-- Donations --")}</th>
            </tr>
            <tr>
                <th style="width: 36px;"></th>
                <th>{t("Name")}</th>
                <th>{t("Shifts")}</th>
                <th scope="col" class="text-end">{t("Unique Count")}</th>
                <th scope="col" class="text-end">{t("Attendance")}</th>
                <th scope="col" class="text-end">{t("Direct")}</th>
                <th scope="col" class="text-end">{t("Indirect")}</th>
            </tr>
        </thead>
        <tbody>
            {#each page.props.events as event (event.id)}
                <tr>
                    <td><img src={event.logo_url} alt="" width="32" height="32" /></td>
                    <td><a href={event.url}>{event.name}</a></td>
                    <td>{event.shifts}</td>
                    <td class="text-end">{event.unique_trooper_count}</td>
                    <td class="text-end">{event.total_trooper_count}</td>
                    <td class="text-end">{event.direct}</td>
                    <td class="text-end">{event.indirect}</td>
                </tr>
            {:else}
                <tr>
                    <td colspan="7" class="text-center py-4">
                        {t("There are not any events that have been closed in the last :days days.", { days })}
                    </td>
                </tr>
            {/each}
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">{t("Total")}</th>
                <th class="text-end">{totals.shifts}</th>
                <th class="text-end">{totals.unique_trooper_count}</th>
                <th class="text-end">{totals.total_trooper_count}</th>
                <th class="text-end">{totals.direct}</th>
                <th class="text-end">{totals.indirect}</th>
            </tr>
        </tfoot>
    </table>
</div>
