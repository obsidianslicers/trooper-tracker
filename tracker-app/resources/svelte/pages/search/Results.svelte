<!-- The search box's results page: matching troopers and events. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { route } from "ziggy-js";
    import ResultsCard from "./components/ResultsCard.svelte";
    import SearchForm from "./components/SearchForm.svelte";
    import type { SearchResultsPageData } from "./models/types";

    let props: SearchResultsPageData = $props();

    // svelte-ignore state_referenced_locally
    pageState.title = props.search_title;

    const escape = (text: string): string =>
        text.replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);

    /** "Showing first 25 results — search troopers only for more." when the limit was hit. */
    const more = (count: number, type: string, label: string): string | null => {
        if (count < props.limit) {
            return null;
        }

        const url = route("search.all", { q: props.term, type });

        return t("Showing first 25 results — :link for more.", {
            link: `<a href="${escape(url)}">${escape(label)}</a>`,
        });
    };
</script>

<div class="row mb-4">
    <div class="col-12">
        <SearchForm term={props.term} type={props.type} types={props.search_types} />
    </div>
</div>

{#if !props.searched}
    <div class="text-center text-muted py-5">
        <i class="fa-solid fa-magnifying-glass fa-3x mb-3 d-block"></i>
        {t("Enter at least 2 characters to search.")}
    </div>
{:else}
    {#if props.troopers}
        <ResultsCard
            title={t("Troopers")}
            icon="fa-user-astronaut"
            count={props.troopers.length}
            empty={t("No troopers found for “:term”.", { term: props.term })}
            more={more(props.troopers.length, "troopers", t("search troopers only"))}
        >
            <thead class="table-dark">
                <tr>
                    <th>{t("Name")}</th>
                    <th>{t("Memberships / IDs")}</th>
                </tr>
            </thead>
            <tbody>
                {#each props.troopers as trooper (trooper.id)}
                    <tr>
                        <td>
                            <a href={trooper.url} class="fw-bold text-decoration-none">{trooper.display_name}</a>
                            {#if trooper.legal_name}
                                <div class="text-muted small">{trooper.legal_name}</div>
                            {/if}
                        </td>
                        <td>
                            {#each trooper.identifiers as identifier (identifier.label)}
                                <span class="badge bg-secondary me-1" title={identifier.club}>{identifier.label}</span>
                            {/each}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </ResultsCard>
    {/if}

    {#if props.events}
        <ResultsCard
            title={t("Events / Troops")}
            icon="fa-calendar-days"
            count={props.events.length}
            empty={t("No events found for “:term”.", { term: props.term })}
            more={more(props.events.length, "events", t("search events only"))}
        >
            <thead class="table-dark">
                <tr>
                    <th>{t("Event")}</th>
                    <th>{t("Club")}</th>
                    <th>{t("Date")}</th>
                    <th>{t("Status")}</th>
                </tr>
            </thead>
            <tbody>
                {#each props.events as event (event.id)}
                    <tr>
                        <td>
                            <a href={event.url} class="fw-bold text-decoration-none">{event.name}</a>
                            {#if event.venue_address}
                                <br /><span class="text-muted small">{event.venue_address}</span>
                            {/if}
                        </td>
                        <td class="text-muted small">{event.organization ?? "—"}</td>
                        <td class="text-nowrap small">{event.date ?? "—"}</td>
                        <td><span class="badge bg-secondary text-uppercase small">{event.status}</span></td>
                    </tr>
                {/each}
            </tbody>
        </ResultsCard>
    {/if}
{/if}
