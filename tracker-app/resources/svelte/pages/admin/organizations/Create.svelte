<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import { CreateViewModel, type CreatePageData } from "./models";

    const page = usePage<CreatePageData>();

    pageState.title = t("Create Organization");

    let vm = new CreateViewModel(page.props);
</script>

<SlimView wide={true}>
    <form onsubmit={vm.submit} novalidate>
        <InputContainer>
            <InputText label={t("Parent")} value={vm.parent.name} disabled={true} />
        </InputContainer>
        <InputContainer>
            <InputText label={t("Name")} bind:value={vm.form.name} errors={vm.errors.name} />
        </InputContainer>
        <SubmitButtonContainer>
            <SubmitButton label={t("Create")} submitting={vm.submitting} />
            <CancelButton href={getRoute("admin.organizations.index")} />
        </SubmitButtonContainer>
    </form>
</SlimView>
