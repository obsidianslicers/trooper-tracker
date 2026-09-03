<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputMarkdown from "$lib/components/form/InputMarkdown.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import type { ISubmitableViewModel } from "$lib/domains/types.svelte";
    import { getRoute } from "$lib/utils";
    import type { IItemForm } from "../models/types";

    interface Props {
        vm: ISubmitableViewModel<IItemForm>;
        label: string;
    }

    let { vm, label }: Props = $props();
</script>

<form onsubmit={vm.submit}>
    <InputContainer>
        <InputSelect
            label="Section"
            bind:value={vm.form.section_id}
            options={vm.sections}
            errors={vm.errors.section_id}
        />
    </InputContainer>

    <InputContainer>
        <InputText
            label="Title"
            bind:value={vm.form.title}
            errors={vm.errors.title}
        />
    </InputContainer>

    <InputContainer>
        <InputMarkdown
            label="Description (Markdown)"
            bind:value={vm.form.description}
            errors={vm.errors.description}
        />
        <InputHelp>
            Leave blank for video-only items. Supports **bold**, *italic*,
            lists, and links.
        </InputHelp>
    </InputContainer>

    <InputContainer>
        <InputText
            label="Video URL"
            bind:value={vm.form.video_url}
            errors={vm.errors.video_url}
        />
        <InputHelp>
            Optional YouTube URL (watch or embed format). Leave blank for
            Q&amp;A items.
        </InputHelp>
    </InputContainer>

    <SubmitButtonContainer>
        <SubmitButton {label} submitting={vm.submitting} disabled={!vm.dirty} />
        <CancelButton href={getRoute("admin.faq.index")} />
    </SubmitButtonContainer>
</form>
