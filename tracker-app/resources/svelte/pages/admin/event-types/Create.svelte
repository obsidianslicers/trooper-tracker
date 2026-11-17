<script lang="ts">
    import { t } from "$lib/i18n";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import EventTypeForm from "./components/EventTypeForm.svelte";
    import { CreateViewModel } from "./models";

    pageState.title = t("Create Event Type");

    let vm = new CreateViewModel();
</script>

<SlimView>
    <EventTypeForm {vm} label={t("Create")} />
</SlimView>
