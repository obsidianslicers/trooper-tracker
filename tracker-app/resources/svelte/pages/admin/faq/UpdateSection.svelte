<script lang="ts">
    import { t } from "$lib/i18n";
    import TrooperStamps from "$lib/components/TrooperStamps.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import SectionForm from "./components/SectionForm.svelte";
    import {
        type UpdateSectionPageData,
        UpdateSectionViewModel,
    } from "./models";

    const page = usePage<UpdateSectionPageData>();

    pageState.title = t("Update FAQ Section");

    let vm = new UpdateSectionViewModel(page.props);
</script>

<SlimView>
    <SectionForm {vm} label={t("Update")} />
    <TrooperStamps trooper_stamps={vm.trooper_stamps} />
</SlimView>
