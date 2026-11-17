<script lang="ts">
    import { t } from "$lib/i18n";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import SectionForm from "./components/SectionForm.svelte";
    import { CreateSectionViewModel } from "./models";

    pageState.title = t("Create FAQ Section");

    let vm = new CreateSectionViewModel();
</script>

<SlimView>
    <SectionForm {vm} label={t("Create")} />
</SlimView>
