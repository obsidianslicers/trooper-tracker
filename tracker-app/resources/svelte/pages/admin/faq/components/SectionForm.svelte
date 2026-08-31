<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import type { ISubmitableViewModel } from "$lib/domains/types.svelte";
    import { getRoute } from "$lib/utils";
    import type { ISectionForm } from "../models/types";

    interface Props {
        vm: ISubmitableViewModel<ISectionForm>;
        label: string;
    }

    let { vm, label }: Props = $props();
</script>

<form onsubmit={vm.submit}>
    <InputContainer>
        <InputText
            label="Label"
            bind:value={vm.form.label}
            errors={vm.errors.label}
        />
    </InputContainer>

    <InputContainer>
        <InputText
            label="Icon"
            bind:value={vm.form.icon}
            errors={vm.errors.icon}
        />
        <InputHelp>
            Font Awesome class, e.g. <code>fa-user-plus</code>
        </InputHelp>
    </InputContainer>

    <SubmitButtonContainer>
        <SubmitButton {label} submitting={vm.submitting} disabled={!vm.dirty} />
        <CancelButton href={getRoute("admin.faq.sections.list")} />
    </SubmitButtonContainer>
</form>
