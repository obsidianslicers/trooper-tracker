<script lang="ts">
    import { untrack } from "svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import { t } from "$lib/i18n";
    import type { DetailsPageData } from "../models";
    import { DetailsViewModel } from "../models";

    interface Props {
        details: DetailsPageData;
    }
    let { details }: Props = $props();

    let vm = new DetailsViewModel(untrack(() => details));

    let currentThemeClass = vm.form.theme
        ? `theme-${vm.form.theme}`
        : "theme-stormtrooper";

    const initialTheme = vm.form.theme;

    let previewingTheme = $derived(vm.form.theme !== initialTheme);

    $effect(() => {
        // SSR Check
        if (typeof document === "undefined") return;

        const newTheme = vm.form.theme;
        const newThemeClass = newTheme ? `theme-${newTheme}` : "";

        // If a theme class was previously applied, remove it
        if (currentThemeClass) {
            document.body.classList.remove(currentThemeClass);
        } else {
            // Fallback: Remove any pre-existing theme classes dynamically
            Array.from(document.body.classList)
                .filter((cls) => cls.startsWith("theme-"))
                .forEach((cls) => document.body.classList.remove(cls));
        }

        // Add the newly selected theme class
        if (newThemeClass) {
            document.body.classList.add(newThemeClass);
            currentThemeClass = newThemeClass;
        }
    });
</script>

<SlimView>
    <form onsubmit={vm.submit}>
        <InputContainer>
            <InputText
                label={t("Legal Name")}
                bind:value={vm.form.legal_name}
                errors={vm.errors.legal_name}
            />
            <InputHelp>
                {t("Used for official records or communications with event staff and coordinators for safety and accountability reasons. This will not be displayed publicly (anonymous viewers).")}
                <br />
                <i>{t("(i.e. Emperor Palpatine)")}</i>
            </InputHelp>
        </InputContainer>
        <InputContainer>
            <InputText
                label={t("Display Name")}
                bind:value={vm.form.display_name}
                errors={vm.errors.display_name}
            />
            <InputHelp>
                {t("The name that will be shown publicly (anonymous viewers).")}
                <br />
                <i>{t("(i.e. Darth Sidious)")}</i>
            </InputHelp>
        </InputContainer>
        <InputContainer>
            <InputText
                label={t("Phone (optional)")}
                bind:value={vm.form.phone}
                errors={vm.errors.phone}
            />
            <InputHelp>
                {t("Your contact phone number. Visible to command staff only.")}
                <br />
                <i>{t("(i.e. 555-555-5555)")}</i>
            </InputHelp>
        </InputContainer>
        {#if vm.display_costumes && vm.display_costumes.length > 0}
            <InputContainer>
                <InputSelect
                    label={t("Preferred 501st Display Format")}
                    bind:value={vm.form.display_costume_id}
                    options={vm.display_costumes}
                    errors={vm.errors.display_costume_id}
                />
                <InputHelp>
                    {t("Choose which prefix and ID to display on the forum (e.g. TK52233 or SL52233). Leave blank to use the first costume automatically.")}
                </InputHelp>
            </InputContainer>
        {/if}
        <InputContainer>
            <InputSelect
                label={t("Theme")}
                bind:value={vm.form.theme}
                options={vm.theme_enums}
                errors={vm.errors.theme}
            />
            <InputHelp>
                {t("Customize the look and feel of the Tracker. This will change the color scheme and fonts of the interface.")}
            </InputHelp>
            {#if previewingTheme}
                <InputHelp>
                    <h4>{t("Previewing Theme")}</h4>
                </InputHelp>
            {/if}
        </InputContainer>
        {#if vm.locale_options.length > 0}
            <InputContainer>
                <InputSelect
                    label={t("Language")}
                    bind:value={vm.form.locale}
                    options={vm.locale_options}
                    errors={vm.errors.locale}
                />
                <InputHelp>
                    {t("The language the Tracker and your emails use.")}
                </InputHelp>
            </InputContainer>
        {/if}
        <SubmitButtonContainer>
            <SubmitButton
                label={t("Save")}
                submitting={vm.submitting}
                disabled={!vm.dirty}
            />
        </SubmitButtonContainer>
    </form>
</SlimView>
