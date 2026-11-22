<script lang="ts">
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import Pagination from "$lib/components/ui/Pagination.svelte";
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import ClubSelect from "./components/ClubSelect.svelte";
    import DateRange from "./components/DateRange.svelte";
    import ReportActions from "./components/ReportActions.svelte";
    import SortHeader from "./components/SortHeader.svelte";
    import type { TrooperEventSummaryPageData } from "./models/summaries";
    import { ReportFiltersViewModel } from "./models/vms/ReportFiltersViewModel.svelte";

    const page = usePage<TrooperEventSummaryPageData>();

    pageState.title = t("Trooper Event Counts");

    const vm = new ReportFiltersViewModel(
        page.props.filters,
        getRoute("admin.reports.trooper-event-summary")
    );

    let troopers = $derived(page.props.troopers);
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
                <div class="col-sm-4 pb-1">
                    <InputCheckbox
                        name="active_only"
                        label={t("Active Members Only")}
                        bind:checked={vm.filters.active_only}
                    />
                </div>
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
                <SortHeader label={t("Trooper")} link={page.props.sorts.display_name} />
                <SortHeader label={t("Unique Events")} link={page.props.sorts.events_count} end />
                <SortHeader label={t("Total Shifts")} link={page.props.sorts.event_shifts_count} end />
            </tr>
        </thead>
        <tbody>
            {#each troopers.data as trooper (trooper.id)}
                <tr>
                    <td><a href={trooper.profile_url}>{trooper.display_name}</a></td>
                    <td class="text-end">{trooper.events_count}</td>
                    <td class="text-end">{trooper.event_shifts_count}</td>
                </tr>
            {:else}
                <tr>
                    <td colspan="3" class="text-center py-4">
                        {t("No troopers found with attended events in the selected date range.")}
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>
<Pagination links={troopers.meta.links} />
