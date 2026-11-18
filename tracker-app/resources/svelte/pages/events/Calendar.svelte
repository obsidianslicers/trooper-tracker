<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import CalendarDay from "./components/CalendarDay.svelte";
    import FilterBar from "./components/FilterBar.svelte";
    import { CalendarViewModel, type CalendarPageData } from "./models";

    const page = usePage<CalendarPageData>();

    pageState.title = t("Event Calendar");

    const vm = new CalendarViewModel(page.props);
</script>

<FilterBar {vm} active="calendar" count={vm.filtered.length} />

<nav class="d-flex flex-nowrap flex-md-wrap overflow-auto gap-1 mb-2 pb-1 calendar-months" aria-label={t("Jump to month")}>
    {#each vm.months as month (month.key)}
        {@const count = vm.countIn(month)}
        <a href="#month-{month.key}" class={["btn btn-sm text-nowrap", count > 0 ? "btn-outline-light" : "btn-outline-secondary"]}>
            {month.label}
            {#if count > 0}<span class="badge rounded-pill text-bg-primary ms-1">{count}</span>{/if}
        </a>
    {/each}
</nav>

<div class="d-flex flex-wrap gap-3 small text-muted mb-3">
    <span><span class="badge border border-success text-success">&nbsp;</span> {t("You're signed up")}</span>
    <span><span class="badge border border-danger text-danger">&nbsp;</span> {t("Risk of cancellation")}</span>
    <span><i class="fa fa-fw fa-star"></i> {t("Holiday")}</span>
</div>

{#each vm.months as month (month.key)}
    <section id="month-{month.key}" class="mt-4" aria-labelledby="month-label-{month.key}">
        <h2 id="month-label-{month.key}" class="h4 border-bottom pb-2">{month.label}</h2>
        <table class="table event-calendar">
            <thead>
                <tr>
                    {#each vm.weekdays as weekday, i (i)}
                        <th class="text-center">{weekday}</th>
                    {/each}
                </tr>
            </thead>
            <tbody>
                {#each month.weeks as week, i (i)}
                    <tr>
                        {#each week as day (day.date)}
                            <CalendarDay {vm} {day} />
                        {/each}
                    </tr>
                {/each}
            </tbody>
        </table>
    </section>
{/each}
