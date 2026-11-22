<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputDate from "$lib/components/form/InputDate.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import type { CopyPageData } from "./models/list-types";
    import { CopyEventViewModel } from "./models/vms/CopyEventViewModel.svelte";

    const page = usePage<CopyPageData>();

    pageState.title = t("Copy Event");

    let vm = new CopyEventViewModel(page.props);
</script>

<SlimView wide={true}>
    <form onsubmit={vm.submit} novalidate>
        <InputContainer>
            <InputText label={t("Hosting Organization")} value={vm.event.organization_name} disabled={true} />
        </InputContainer>
        <InputContainer>
            <InputText label={t("Name")} bind:value={vm.form.name} errors={vm.errors.name} />
        </InputContainer>
        <InputContainer>
            <InputDate
                label={t("New Scheduled Start")}
                with_time={true}
                placeholder={null}
                bind:value={vm.form.event_start}
                errors={vm.errors.event_start}
            />
        </InputContainer>
        <SubmitButtonContainer>
            <SubmitButton label={t("Copy")} submitting={vm.submitting} />
            <CancelButton href={vm.cancel_url} />
        </SubmitButtonContainer>
    </form>
</SlimView>
