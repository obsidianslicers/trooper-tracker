<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import { getRoute } from "$lib/utils";
    import type { FaqSectionFormViewModel } from "../../models";

    interface Props {
        vm: FaqSectionFormViewModel;
    }

    let { vm }: Props = $props();
</script>

<SlimCard>
    <form onsubmit={vm.submit}>
        <InputContainer>
            <InputText label="Label" bind:value={vm.form.label} errors={vm.errors.label} />
        </InputContainer>

        <InputContainer>
            <InputText label="Icon" bind:value={vm.form.icon} errors={vm.errors.icon} />
            <InputHelp>Font Awesome class, e.g. <code>fa-user-plus</code></InputHelp>
        </InputContainer>

        <SubmitButtonContainer>
            <SubmitButton
                label={vm.mode === "create" ? "Create" : "Update"}
                submitting={vm.submitting}
                disabled={!vm.dirty}
            />
            <CancelButton href={getRoute("admin.faq.sections.list")} />
        </SubmitButtonContainer>
    </form>
</SlimCard>
