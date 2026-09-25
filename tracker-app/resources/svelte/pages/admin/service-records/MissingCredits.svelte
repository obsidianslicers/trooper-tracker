<script lang="ts">
    import Button from "$lib/components/ui/buttons/Button.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import MissingCreditRow from "./components/MissingCreditRow.svelte";
    import { MissingCreditsViewModel, type MissingCreditsPageData } from "./models";

    const page = usePage<MissingCreditsPageData>();

    pageState.title = "Missing Credit";

    let vm = new MissingCreditsViewModel(page.props);

    let emptyStateMessage = $derived(
        vm.filtered_trooper
            ? "Job well done! No missing-credit shifts found for this trooper."
            : "Job well done! No missing-credit shifts found."
    );
</script>

{#if vm.filtered_trooper}
    <p class="text-muted small mb-3">
        Showing missing-credit shifts for
        <a href={vm.trooperServiceRecordRoute(vm.filtered_trooper.id)}>
            {vm.filtered_trooper.display_name}
        </a>.
        <a href={vm.clear_filter_route}>View all missing credit</a>
    </p>
{/if}

{#if vm.rows.length === 0 && !vm.has_more}
    <div class="alert alert-success">
        <i class="fa fa-fw fa-circle-check me-1"></i>
        {emptyStateMessage}
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
            <Button
                btnclass="btn-outline-secondary"
                label={vm.loading_more ? "Loading…" : "Load More"}
                icon={vm.loading_more ? "fa-solid fa-spinner fa-spin" : null}
                disabled={vm.loading_more}
                click={() => vm.loadMore()}
            />
        </div>
    {/if}
{/if}
