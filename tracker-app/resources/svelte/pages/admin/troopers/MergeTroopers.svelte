<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import TrooperPicker from "$lib/components/TrooperPicker.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { MergeTroopersViewModel } from "./models";

    pageState.title = t("Merge Troopers");

    const vm = new MergeTroopersViewModel();
</script>

<SlimCard>
    <h4 class="text-danger text-center mb-3">
        <i class="fa fa-fw fa-exclamation-triangle"></i>
        {t("This operation cannot be undone or reversed.")}
    </h4>
    <form onsubmit={vm.submit}>
        <InputContainer>
            <TrooperPicker
                bind:selected={vm.source_trooper}
                onSelected={(t) => (vm.source_trooper = t)}
                errors={vm.errors.source_trooper_id}
                mode="admin"
                label={t("Source Trooper")}
            />
            <InputHelp>
                {t("Select the trooper you want to merge from. This account will be set to MERGED and will be unavailable for further use.")}
            </InputHelp>
        </InputContainer>
        <InputContainer>
            <TrooperPicker
                bind:selected={vm.target_trooper}
                onSelected={(t) => (vm.target_trooper = t)}
                errors={vm.errors.target_trooper_id}
                mode="admin"
                label={t("Target Trooper")}
            />
            <InputHelp>{t("Select the trooper you want to merge into.")}</InputHelp>
        </InputContainer>

        <SubmitButtonContainer>
            <SubmitButton
                label={t("Merge Troopers")}
                submitting={vm.submitting}
                disabled={!vm.dirty}
            />
        </SubmitButtonContainer>
    </form>
</SlimCard>
