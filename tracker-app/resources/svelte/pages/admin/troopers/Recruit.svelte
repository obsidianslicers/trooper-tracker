<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import OrganizationPicker from "$lib/components/OrganizationPicker.svelte";
    import TrooperPicker from "$lib/components/TrooperPicker.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import type { RecruitPageData } from "./models/list-types";
    import { RecruitViewModel } from "./models/vms/RecruitViewModel.svelte";

    const page = usePage<RecruitPageData>();

    pageState.title = t("Recruit Trooper");

    let vm = new RecruitViewModel(page.props);
</script>

<SlimView wide={true}>
    <form onsubmit={vm.submit} novalidate>
        <InputContainer>
            <TrooperPicker
                bind:selected={vm.trooper}
                label={t("Trooper")}
                placeholder={t("(Search for a Trooper)")}
                modal_title={t("Find a Trooper")}
                errors={vm.errors.trooper_id}
            />
        </InputContainer>
        <InputContainer>
            <OrganizationPicker
                options={page.props.organization_options}
                label={t("Assign to Unit")}
                placeholder={t("— Select a Unit —")}
                modal_title={t("Assign to Unit")}
                bind:value={vm.form.organization_id}
                errors={vm.errors.organization_id}
            />
        </InputContainer>
        {#if vm.identifier_label}
            <InputContainer>
                <InputText label={vm.identifier_label} bind:value={vm.form.identifier} errors={vm.errors.identifier} />
            </InputContainer>
        {/if}
        <SubmitButtonContainer>
            <SubmitButton label={t("Add to Roster")} submitting={vm.submitting} />
            <CancelButton href={getRoute("admin.troopers.index")} />
        </SubmitButtonContainer>
    </form>
</SlimView>
