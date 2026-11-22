<script lang="ts">
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import Pagination from "$lib/components/ui/Pagination.svelte";
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import DateRange from "./components/DateRange.svelte";
    import DonationEvent from "./components/DonationEvent.svelte";
    import DonationTotals from "./components/DonationTotals.svelte";
    import ReportActions from "./components/ReportActions.svelte";
    import type { DonationEventSummaryPageData } from "./models/summaries";
    import { ReportFiltersViewModel } from "./models/vms/ReportFiltersViewModel.svelte";

    const page = usePage<DonationEventSummaryPageData>();

    pageState.title = t("Donation Event Summary");

    const vm = new ReportFiltersViewModel(
        page.props.filters,
        getRoute("admin.reports.donation-event-summary")
    );

    const directions = [
        { value: "desc", label: t("Descending") },
        { value: "asc", label: t("Ascending") },
    ];

    let events = $derived(page.props.events);
</script>

<div class="card mb-3">
    <div class="card-header">{t("Filters")}</div>
    <div class="card-body">
        <form method="GET" novalidate onsubmit={vm.run}>
            <div class="row g-3 align-items-end">
                <DateRange bind:date_start={vm.filters.date_start} bind:date_end={vm.filters.date_end} />
                <div class="col-sm-4 pb-1">
                    <InputCheckbox
                        name="charity_only"
                        label={t("Events with charity data only")}
                        bind:checked={vm.filters.charity_only}
                    />
                </div>
            </div>

            {#if page.props.clubs.length > 1}
                <div class="row g-3 mt-1">
                    <div class="col-sm-4">
                        <div class="form-label">{t("Clubs")}</div>
                        <div class="border rounded p-2 overflow-auto" style="max-height: 200px;">
                            {#each page.props.clubs as club (club.value)}
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="organization_ids[]"
                                        id="org_{club.value}"
                                        value={club.value}
                                        bind:group={vm.filters.organization_ids}
                                    />
                                    <label class="form-check-label" for="org_{club.value}">
                                        {club.label}
                                    </label>
                                </div>
                            {/each}
                        </div>
                    </div>
                </div>
            {/if}

            <div class="row g-3 mt-1">
                <div class="col-sm-4">
                    <div class="form-floating">
                        <select id="sort" name="sort" class="form-select" bind:value={vm.filters.sort}>
                            {#each page.props.sort_options as option (option.value)}
                                <option value={option.value}>{option.label}</option>
                            {/each}
                        </select>
                        <label for="sort">{t("Sort By")}</label>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-floating">
                        <select id="dir" name="dir" class="form-select" bind:value={vm.filters.dir}>
                            {#each directions as option (option.value)}
                                <option value={option.value}>{option.label}</option>
                            {/each}
                        </select>
                        <label for="dir">{t("Direction")}</label>
                    </div>
                </div>
            </div>

            <ReportActions csv_url={page.props.csv_url} />
        </form>
    </div>
</div>

{#if page.props.caption}
    <p class="text-muted small mb-2">{page.props.caption}</p>
{/if}

{#each events.data as event (event.id)}
    <DonationEvent {event} />
{:else}
    <div class="alert alert-info">{t("No closed events found for the selected filters.")}</div>
{/each}

<div class="mb-3">
    <Pagination links={events.meta.links} />
</div>

{#if page.props.grand_totals}
    <DonationTotals totals={page.props.grand_totals} />
{/if}
