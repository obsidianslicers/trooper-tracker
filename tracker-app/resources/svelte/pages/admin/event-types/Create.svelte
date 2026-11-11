<script lang="ts">
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import EventTypeForm from "./components/EventTypeForm.svelte";
    import { CreateViewModel } from "./models";

    pageState.title = "Create Event Type";

    let vm = new CreateViewModel();
</script>

<SlimView>
    <EventTypeForm {vm} label="Create" />
</SlimView>
