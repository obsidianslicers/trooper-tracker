<script lang="ts">
    import { locale, t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import FilterBar from "./components/FilterBar.svelte";
    import { PastEventsViewModel, type PastEventsPageData } from "./models";

    const page = usePage<PastEventsPageData>();

    const vm = new PastEventsViewModel(page.props);

    pageState.title = vm.is_cancelled ? t("Recently Cancelled Events") : t("Completed Events");

    const totals = $derived(page.props.totals);
    const pagination = $derived(page.props.pagination);

    const whole = new Intl.NumberFormat(locale());
    const money = new Intl.NumberFormat(locale(), { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<FilterBar {vm} active={vm.is_cancelled ? "cancelled" : "closed"} count={vm.filtered.length} />

<p class="small text-muted mb-2">
    {#if vm.is_cancelled}
        {t("Summary of Cancelled Events over the last :days days.", { days: vm.lookback_days ?? 0 })}
    {:else}
        {t("Closed event history, newest first.")}
    {/if}
</p>

<div class="table-responsive">
    <table class="table align-middle past-events">
        <thead>
            <tr>
                <th>{t("Name")}</th>
                <th class="text-end">{t("Shifts")}</th>
                {#if !vm.is_cancelled}
                    <th class="text-end">{t("Unique")}</th>
                    <th class="text-end">{t("Total")}</th>
                {/if}
                <th class="text-end">{t("Direct")}</th>
                <th class="text-end">{t("Indirect")}</th>
            </tr>
        </thead>
        <tbody>
            {#each vm.filtered as event (event.id)}
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src={event.logo_url} alt="" width="32" height="32" class="flex-shrink-0" />
                            <div class="min-w-0">
                                <a href={event.url} class="fw-semibold">{event.name}</a>
                                <div class="small text-muted">
                                    {event.date_label}{#if event.organization_name}&nbsp;· {event.organization_name}{/if}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-end">{whole.format(event.shifts)}</td>
                    {#if !vm.is_cancelled}
                        <td class="text-end">{whole.format(event.unique_troopers)}</td>
                        <td class="text-end">{whole.format(event.total_troopers)}</td>
                    {/if}
                    <td class="text-end">{money.format(event.direct_funds)}</td>
                    <td class="text-end">{money.format(event.indirect_funds)}</td>
                </tr>
            {:else}
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        {#if vm.events.length > 0}
                            {t("No events match your filters.")}
                        {:else if vm.is_cancelled}
                            {t("There are not any events that have been cancelled in the last :days days.", { days: vm.lookback_days ?? 0 })}
                        {:else}
                            {t("No closed events found.")}
                        {/if}
                    </td>
                </tr>
            {/each}
        </tbody>
        {#if vm.events.length > 0}
            <tfoot>
                <tr>
                    <th>
                        {whole.format(pagination?.total ?? vm.events.length)}
                        <span class="text-muted fw-normal">
                            {vm.is_cancelled ? t("Events in Total") : t("Closed Events")}
                        </span>
                        <div class="small text-muted fw-normal">
                            {#if vm.is_cancelled}
                                {t("* Totals only all include events that have been closed within the lookback period (not filtered).")}
                            {:else}
                                {t("* Summary totals are for the events shown on this page (not filtered).")}
                            {/if}
                        </div>
                    </th>
                    <th class="text-end">{whole.format(totals.shifts)}</th>
                    {#if !vm.is_cancelled}
                        <th class="text-end">{whole.format(totals.unique_troopers)}</th>
                        <th class="text-end">{whole.format(totals.total_troopers)}</th>
                    {/if}
                    <th class="text-end">{money.format(totals.direct_funds)}</th>
                    <th class="text-end">{money.format(totals.indirect_funds)}</th>
                </tr>
            </tfoot>
        {/if}
    </table>
</div>

{#if pagination && pagination.last_page > 1}
    <nav class="d-flex justify-content-center align-items-center gap-3" aria-label={t("Pages")}>
        {#if pagination.prev_url}
            <a href={pagination.prev_url} class="btn btn-sm btn-outline-light">
                <i class="fa fa-fw fa-chevron-left"></i>{t("Newer")}
            </a>
        {/if}
        <span class="small text-muted">
            {t("Page :page of :pages", { page: pagination.current_page, pages: pagination.last_page })}
        </span>
        {#if pagination.next_url}
            <a href={pagination.next_url} class="btn btn-sm btn-outline-light">
                {t("Older")}<i class="fa fa-fw fa-chevron-right"></i>
            </a>
        {/if}
    </nav>
{/if}
