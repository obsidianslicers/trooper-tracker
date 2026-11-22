<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import OrganizationPicker from "$lib/components/OrganizationPicker.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import { CreateViewModel, type CreatePageData } from "./models";

    const page = usePage<CreatePageData>();

    pageState.title = t("Create Award");

    let vm = new CreateViewModel(page.props);
</script>

<SlimView wide={true}>
    <form onsubmit={vm.submit} novalidate>
        <InputContainer>
            <OrganizationPicker
                options={vm.organization_options}
                bind:value={vm.form.organization_id}
                errors={vm.errors.organization_id}
            />
        </InputContainer>
        <InputContainer>
            <InputSelect
                label={t("Frequency")}
                options={vm.frequency_options}
                bind:value={vm.form.frequency}
                errors={vm.errors.frequency}
            />
        </InputContainer>
        <InputContainer>
            <InputText label={t("Name")} bind:value={vm.form.name} errors={vm.errors.name} />
        </InputContainer>
        <SubmitButtonContainer>
            <SubmitButton label={t("Create")} submitting={vm.submitting} />
            <CancelButton href={vm.cancel_url} />
        </SubmitButtonContainer>
    </form>
</SlimView>
