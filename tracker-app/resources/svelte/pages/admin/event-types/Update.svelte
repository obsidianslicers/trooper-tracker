<script lang="ts">
    import PluginSlot from "$lib/components/PluginSlot.svelte";
    import TrooperStamps from "$lib/components/TrooperStamps.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import EventTypeForm from "./components/EventTypeForm.svelte";
    import { type UpdatePageData, UpdateViewModel } from "./models";

    const page = usePage<UpdatePageData>();

    pageState.title = "Update Event Type";

    let vm = new UpdateViewModel(page.props);
</script>

<SlimView>
    <EventTypeForm {vm} label="Update">
        {#snippet counted_notice()}
            {#if vm.changes_past_totals}
                <div class="alert alert-warning small mt-2 mb-0">
                    This also applies to the {vm.events_count} event(s) already using
                    this type, including past ones.
                    Leaderboards change right away and troop totals overnight.
                    Milestones already reached are kept.
                </div>
            {/if}
        {/snippet}
    </EventTypeForm>
    <PluginSlot name="admin.event-types.update.sections" />
    <TrooperStamps trooper_stamps={vm.trooper_stamps} />
</SlimView>
