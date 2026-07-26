<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import TrooperPicker from "$lib/components/TrooperPicker.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import { MergeTroopersViewModel } from "$lib/domains/troopers/vms/MergeTroopersViewModel.svelte";
    import pageState from "$lib/states/page-state.svelte";

    type Trooper = {
        id: string | number;
        [key: string]: unknown;
    };

    pageState.title = "Merge Troopers";

    const vm = new MergeTroopersViewModel();
</script>

<SlimCard>
    <h4 class="text-danger text-center mb-3">
        <i class="fa fa-fw fa-exclamation-triangle"></i>
        This operation cannot be undone or reversed.
    </h4>
    <InputContainer>
        <TrooperPicker
            bind:selected={vm.source_trooper}
            onSelect={(t) => (vm.source_trooper = t)}
            errors={vm.errors.source_trooper_id}
            mode="admin"
            label="Source Trooper"
        />
        <InputHelp>
            Select the trooper you want to merge from. This account will be set
            to MERGED and will be unavailable for further use.
        </InputHelp>
    </InputContainer>
    <InputContainer>
        <TrooperPicker
            bind:selected={vm.target_trooper}
            onSelect={(t) => (vm.target_trooper = t)}
            errors={vm.errors.target_trooper_id}
            mode="admin"
            label="Target Trooper"
        />
        <InputHelp>Select the trooper you want to merge into.</InputHelp>
    </InputContainer>

    <SubmitButtonContainer>
        <SubmitButton
            label="Merge Troopers"
            submitting={vm.submitting}
            disabled={!vm.dirty}
        />
    </SubmitButtonContainer>
</SlimCard>
