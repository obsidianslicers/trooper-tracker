<script lang="ts">
    import Pagination from "$lib/components/ui/Pagination.svelte";
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import ClubSelect from "./components/ClubSelect.svelte";
    import DateRange from "./components/DateRange.svelte";
    import ReportActions from "./components/ReportActions.svelte";
    import SortHeader from "./components/SortHeader.svelte";
    import type { CostumeEventSummaryPageData } from "./models/summaries";
    import { ReportFiltersViewModel } from "./models/vms/ReportFiltersViewModel.svelte";

    const page = usePage<CostumeEventSummaryPageData>();

    pageState.title = t("Costume Event Summary");

    const vm = new ReportFiltersViewModel(
        page.props.filters,
        getRoute("admin.reports.costume-event-summary")
    );

    let costumes = $derived(page.props.costumes);
    let totals = $derived(page.props.totals);
</script>

<div class="card mb-3">
    <div class="card-header">{t("Filters")}</div>
    <div class="card-body">
        <form method="GET" novalidate onsubmit={vm.run}>
            <div class="row g-3 align-items-end">
                <div class="col-sm-4">
                    <ClubSelect bind:value={vm.filters.organization_id} options={page.props.organizations} />
                </div>
                <DateRange bind:date_start={vm.filters.date_start} bind:date_end={vm.filters.date_end} />
            </div>
            <ReportActions csv_url={page.props.csv_url} />
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table caption-top">
        <caption>{page.props.caption}</caption>
        <thead>
            <tr>
                <SortHeader label={t("Costume")} link={page.props.sorts.name} />
                <SortHeader label={t("Unique Events")} link={page.props.sorts.events_count} end />
                <SortHeader label={t("Total Uses")} link={page.props.sorts.uses_count} end />
            </tr>
        </thead>
        <tbody>
            {#each costumes.data as costume (costume.id)}
                <tr>
                    <td>{costume.name}</td>
                    <td class="text-end">{costume.events_count}</td>
                    <td class="text-end">{costume.uses_count}</td>
                </tr>
            {:else}
                <tr>
                    <td colspan="3" class="text-center py-4">
                        {t("No costumes found with attended events in the selected date range.")}
                    </td>
                </tr>
            {/each}
        </tbody>
        {#if totals}
            <tfoot>
                <tr>
                    <th>{t("Total")}</th>
                    <th class="text-end">{totals.events_count}</th>
                    <th class="text-end">{totals.uses_count}</th>
                </tr>
            </tfoot>
        {/if}
    </table>
</div>
<Pagination links={costumes.meta.links} />
