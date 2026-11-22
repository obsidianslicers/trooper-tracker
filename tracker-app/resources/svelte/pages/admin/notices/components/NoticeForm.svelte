<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputDate from "$lib/components/form/InputDate.svelte";
    import InputMarkdown from "$lib/components/form/InputMarkdown.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import type { NoticeFormViewModel } from "../models";

    interface Props {
        vm: NoticeFormViewModel;
        label: string;
    }

    let { vm, label }: Props = $props();
</script>

<form onsubmit={vm.submit} novalidate>
    <InputContainer>
        {#if vm.is_update}
            <InputText
                label={t("Organization")}
                value={vm.organization_name ?? t("Everyone")}
                disabled={true}
            />
        {:else}
            <InputSelect
                label={t("Organization")}
                options={vm.organization_options}
                placeholder={vm.can_post_to_everyone ? null : t("Select an Organization")}
                bind:value={vm.form.organization_id}
                errors={vm.errors.organization_id}
            />
        {/if}
    </InputContainer>

    <InputContainer>
        <InputSelect
            label={t("Type")}
            options={vm.type_options}
            bind:value={vm.form.type}
            errors={vm.errors.type}
        />
    </InputContainer>

    <InputContainer>
        <InputText label={t("Title")} bind:value={vm.form.title} errors={vm.errors.title} />
    </InputContainer>

    <div class="row">
        <div class="col-6 mb-3">
            <InputDate
                label={t("Starts")}
                with_time={true}
                placeholder={null}
                bind:value={vm.form.starts_at}
                errors={vm.errors.starts_at}
            />
        </div>
        <div class="col-6 mb-3">
            <InputDate
                label={t("Ends")}
                with_time={true}
                placeholder={null}
                bind:value={vm.form.ends_at}
                errors={vm.errors.ends_at}
            />
        </div>
    </div>

    <InputContainer>
        <InputMarkdown
            label={t("Message")}
            bind:value={vm.form.message}
            errors={vm.errors.message}
        />
    </InputContainer>

    <SubmitButtonContainer>
        <SubmitButton {label} submitting={vm.submitting} disabled={vm.is_update && !vm.dirty} />
        <CancelButton href={vm.cancel_url} />
    </SubmitButtonContainer>
</form>
