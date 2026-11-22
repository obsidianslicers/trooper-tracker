<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputDate from "$lib/components/form/InputDate.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import TrooperPicker from "$lib/components/TrooperPicker.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import AwardSummary from "./components/AwardSummary.svelte";
    import { AssignTrooperViewModel, type AssignTrooperPageData } from "./models";

    const page = usePage<AssignTrooperPageData>();

    pageState.title = t("Award Trooper");

    let vm = new AssignTrooperViewModel(page.props);
</script>

<SlimView wide={true}>
    <form onsubmit={vm.submit} novalidate>
        <AwardSummary award={vm.award} with_frequency={false} />
        <InputContainer>
            <InputDate
                label={t("Award Date")}
                bind:value={vm.form.award_date}
                errors={vm.errors.award_date}
            />
        </InputContainer>
        <InputContainer>
            <TrooperPicker
                label={t("Trooper")}
                mode="moderated"
                modal_title={t("Find a Trooper")}
                bind:selected={vm.trooper}
                onSelected={vm.pick}
                errors={vm.errors.trooper_id}
            />
        </InputContainer>
        <SubmitButtonContainer>
            <SubmitButton label={t("Submit")} submitting={vm.submitting} />
            <CancelButton href={vm.cancel_url} />
        </SubmitButtonContainer>
    </form>
</SlimView>
