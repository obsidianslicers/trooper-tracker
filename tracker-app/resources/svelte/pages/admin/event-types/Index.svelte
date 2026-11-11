<script lang="ts">
    import { sortable } from "$lib/actions/sortable";
    import DeleteConfirmationModal from "$lib/components/DeleteConfirmationModal.svelte";
    import CreateButton from "$lib/components/ui/buttons/CreateButton.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import EventTypeRow from "./components/EventTypeRow.svelte";
    import { IndexViewModel, type IndexPageData } from "./models";

    const page = usePage<IndexPageData>();

    pageState.title = "Event Types";

    let vm = new IndexViewModel(page.props);
</script>

<p class="text-muted small mb-2">
    <i class="fa fa-fw fa-grip-vertical me-1"></i>
    Drag rows to set the order of the Event Type list on the event form. The first
    type shown is picked for new events.
</p>

<div class="table-responsive mt-3">
    <table class="table align-middle">
        <thead>
            <tr>
                <th style="width: 80px;">Order</th>
                <th>Event Type</th>
                <th style="width: 140px;">Troop credit</th>
                <th style="width: 80px;">Events</th>
                <th class="text-end">
                    <CreateButton
                        label="Event Type"
                        href={getRoute("admin.event-types.create")}
                        small={true}
                    />
                </th>
            </tr>
        </thead>
        <tbody {@attach sortable({ onReorderComplete: vm.reorder })}>
            {#each vm.event_types as event_type (event_type.id)}
                <EventTypeRow {vm} {event_type} />
            {/each}
        </tbody>
    </table>
</div>

<DeleteConfirmationModal
    show={vm.show_delete}
    label={vm.delete_event_type?.name}
    deleting={vm.deleting}
    onDelete={vm.deleteEventType}
    onCancel={vm.cancelDelete}
/>
