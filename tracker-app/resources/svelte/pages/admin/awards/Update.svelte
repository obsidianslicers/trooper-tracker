<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import TrooperStamps from "$lib/components/TrooperStamps.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import AwardTabs from "./components/AwardTabs.svelte";
    import { UpdateViewModel, type UpdatePageData } from "./models";

    const page = usePage<UpdatePageData>();

    pageState.title = t("Update Award");

    let vm = new UpdateViewModel(page.props);
</script>

<AwardTabs award_id={vm.award.id!} active="overview" />

<SlimView wide={true}>
    <form onsubmit={vm.submit} novalidate>
        <InputContainer>
            <InputText label={t("Organization")} value={vm.award.organization_name} disabled={true} />
        </InputContainer>
        <InputContainer>
            <InputText label={t("Frequency")} value={vm.award.frequency_label} disabled={true} />
        </InputContainer>
        <InputContainer>
            <InputText label={t("Name")} bind:value={vm.form.name} errors={vm.errors.name} />
        </InputContainer>
        <SubmitButtonContainer>
            <SubmitButton label={t("Update")} submitting={vm.submitting} disabled={!vm.dirty} />
            <CancelButton href={getRoute("admin.awards.list")} />
        </SubmitButtonContainer>
    </form>
    <TrooperStamps trooper_stamps={vm.trooper_stamps} />
</SlimView>
