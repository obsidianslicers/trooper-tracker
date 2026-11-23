<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputPassword from "$lib/components/form/InputPassword.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import { UpdateSettingsViewModel, type UpdateSettingsPageData } from "./models";

    const page = usePage<UpdateSettingsPageData>();

    pageState.title = t(":plugin Settings", { plugin: page.props.plugin.name });

    let vm = new UpdateSettingsViewModel(page.props);

    const yes_no = [
        { value: "1", label: t("Yes") },
        { value: "0", label: t("No") },
    ];
</script>

<SlimView wide={true}>
    <form onsubmit={vm.submit} novalidate>
        {#each vm.fields as field (field.key)}
            <InputContainer>
                {#if field.type === "secret"}
                    <InputPassword
                        label={field.label}
                        bind:value={vm.form.settings[field.key]}
                        errors={vm.errorsFor(field)}
                    />
                    {#if field.is_saved}
                        <InputHelp>{t("Saved. Leave empty to keep it.")}</InputHelp>
                    {/if}
                {:else if field.type === "boolean"}
                    <InputSelect
                        label={field.label}
                        options={yes_no}
                        bind:value={vm.form.settings[field.key]}
                        errors={vm.errorsFor(field)}
                    />
                {:else}
                    <InputText
                        label={field.label}
                        bind:value={vm.form.settings[field.key]}
                        errors={vm.errorsFor(field)}
                    />
                {/if}
                {#if field.help}
                    <InputHelp>{field.help}</InputHelp>
                {/if}
            </InputContainer>
        {/each}
        <SubmitButtonContainer>
            <SubmitButton label={t("Save")} submitting={vm.submitting} />
            <CancelButton href={getRoute("admin.plugins.index")} />
        </SubmitButtonContainer>
    </form>
</SlimView>
