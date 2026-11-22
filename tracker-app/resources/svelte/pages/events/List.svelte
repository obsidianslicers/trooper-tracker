<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { Deferred, usePage } from "@inertiajs/svelte";
    import EventCard from "./components/EventCard.svelte";
    import EventFilters from "./components/EventFilters.svelte";
    import NoticeAlert from "./components/NoticeAlert.svelte";
    import SupportGoal from "./components/SupportGoal.svelte";
    import { ListEventsViewModel, type ListEventsPageData } from "./models";

    const page = usePage<ListEventsPageData>();

    pageState.title = t("Upcoming Events");

    const vm = new ListEventsViewModel(page.props);
</script>

<NoticeAlert {vm} />

{#if page.props.support !== null}
    <Deferred data="support">
        {#snippet fallback()}{/snippet}
        <SupportGoal support={page.props.support!} />
    </Deferred>
{/if}

<EventFilters {vm} />

{#if vm.months.length === 0}
    <div class="text-center text-muted py-5 events-empty">
        <i class="fa fa-fw fa-calendar-xmark fa-3x mb-3"></i>
        {#if vm.events.length === 0}
            <p class="lead mb-0">{t("No upcoming events yet. Check back soon.")}</p>
        {:else}
            <p class="lead">{t("No events match your filters.")}</p>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action" onclick={vm.clearFilters}>
                {t("Clear Filters")}
            </button>
        {/if}
    </div>
{/if}

{#each vm.months as month (month.key)}
    <section class="mt-4" aria-labelledby="month-{month.key}">
        <h2 id="month-{month.key}" class="h5 text-uppercase text-muted border-bottom pb-2 mb-3">
            {month.label}
            <span class="badge rounded-pill text-bg-secondary ms-2 align-middle">
                {month.events.length}
            </span>
        </h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 event-cards">
            {#each month.events as event (event.id)}
                <EventCard {vm} {event} />
            {/each}
        </div>
    </section>
{/each}

