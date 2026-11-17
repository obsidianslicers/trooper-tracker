<script lang="ts">
    import { t } from "$lib/i18n";
    import CostumePicker from "$lib/components/CostumePicker.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { MergeCostumesViewModel } from "./models";

    pageState.title = t("Merge Costumes");

    const vm = new MergeCostumesViewModel();
</script>

<SlimCard>
    <h4 class="text-danger text-center mb-3">
        <i class="fa fa-fw fa-exclamation-triangle"></i>
        {t("This operation cannot be undone or reversed.")}
    </h4>
    <form onsubmit={vm.submit}>
        <InputContainer>
            <CostumePicker
                bind:selected={vm.source_costume}
                onSelected={(c) => (vm.source_costume = c)}
                errors={vm.errors.source_costume_id}
                label={t("Duplicate Costume")}
            />
            <InputHelp>
                {t("Select the duplicate costume. Its clubs, troopers, and event sign-ups move to the costume you keep, then it is permanently deleted.")}
            </InputHelp>
        </InputContainer>
        <InputContainer>
            <CostumePicker
                bind:selected={vm.target_costume}
                onSelected={(c) => (vm.target_costume = c)}
                errors={vm.errors.target_costume_id}
                label={t("Costume to Keep")}
            />
            <InputHelp>
                {t("Select the costume to keep. Club syncs match costumes by name, so keep the one whose name matches the club's data.")}
            </InputHelp>
        </InputContainer>

        <SubmitButtonContainer>
            <SubmitButton
                label={t("Merge Costumes")}
                submitting={vm.submitting}
                disabled={!vm.can_submit}
            />
        </SubmitButtonContainer>
    </form>
</SlimCard>
