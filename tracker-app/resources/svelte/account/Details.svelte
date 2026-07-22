<script lang="ts">
    import InputSelect from '$lib/components/form/InputSelect.svelte';
    import InputText from '$lib/components/form/InputText.svelte';
    import SubmitButtonContainer from '$lib/components/form/SubmitButtonContainer.svelte';
    import CancelButton from '$lib/components/ui/buttons/CancelButton.svelte';
    import SubmitButton from '$lib/components/ui/buttons/SubmitButton.svelte';
    import SlimView from '$lib/components/ui/SlimView.svelte';
    import type { Details } from '$lib/domains/account';
    import { DetailsViewModel } from '$lib/domains/account';
    import configStateSvelte from '$lib/states/config-state.svelte';
    import { onMount } from 'svelte';

    interface Props {
        details: Details;
    }
    let { details }: Props = $props();

    let vm = new DetailsViewModel();

    onMount(() => {
        vm.load(details);
    });
</script>

<SlimView>
    <InputText
        label="Legal Name"
        bind:value={vm.details.legalName}
        errors={vm.errors.legalName}
    />
    <InputText
        label="Email"
        bind:value={vm.details.email}
        errors={vm.errors.email}
    />
    <InputSelect
        label="Theme"
        bind:value={vm.details.theme}
        options={configStateSvelte.getEnumOptions('trooperTheme')}
        errors={vm.errors.theme}
    />
    {#if vm.dirty}
        <SubmitButtonContainer>
            <SubmitButton
                label="Save"
                submitting={vm.submitting}
                click={vm.update}
            />
            <CancelButton click={vm.revert} />
        </SubmitButtonContainer>
    {/if}
</SlimView>
