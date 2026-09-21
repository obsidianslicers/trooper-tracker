<script lang="ts">
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import MissingCreditRow from "./components/MissingCreditRow.svelte";
    import { MissingCreditsViewModel, type MissingCreditsPageData } from "./models";

    const page = usePage<MissingCreditsPageData>();

    pageState.title = "Missing Credit";

    let vm = new MissingCreditsViewModel(page.props);
</script>

{#if vm.filtered_trooper}
    <p class="text-muted small mb-3">
        Showing missing-credit shifts for
        <a href={vm.trooperServiceRecordRoute(vm.filtered_trooper.id)}>{vm.filtered_trooper.display_name}</a>.
        <a href={vm.clear_filter_route}>View all missing credit</a>
    </p>
{/if}

{#if vm.rows.length === 0 && !vm.has_more}
    <div class="alert alert-success">
        <i class="fa fa-fw fa-circle-check me-1"></i>
        Job well done! No missing-credit shifts found{vm.filtered_trooper ? " for this trooper" : ""}.
    </div>
{:else}
    {#if vm.total > 0}
        <p class="text-muted small mb-2">Showing {vm.rows.length} of {vm.total}</p>
    {/if}

    {#if vm.rows.length > 0}
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Trooper</th>
                        <th>Event</th>
                        <th>Shift</th>
                        <th>Costume</th>
                        <th>Assign Credit To</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {#each vm.rows as row (row.event_trooper_id)}
                        <MissingCreditRow {vm} {row} />
                    {/each}
                </tbody>
            </table>
        </div>
    {/if}

    {#if vm.has_more}
        <div class="text-center mt-3">
            <button type="button" class="btn btn-outline-secondary" disabled={vm.loading_more} onclick={() => vm.loadMore()}>
                {vm.loading_more ? "Loading…" : "Load More"}
            </button>
        </div>
    {/if}
{/if}
