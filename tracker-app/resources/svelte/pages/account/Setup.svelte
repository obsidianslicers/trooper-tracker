<!-- The first-time setup form: email, legal name, theme and email frequency. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import Alert from "$lib/components/ui/Alert.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import type { SetupPageData } from "./models/onboarding-types";
    import { SetupViewModel } from "./models/vms/SetupViewModel.svelte";

    let { trooper, memberships }: SetupPageData = $props();

    pageState.title = t("Trooper Profile");

    // svelte-ignore state_referenced_locally
    const vm = new SetupViewModel(trooper);
</script>

<SlimCard>
    <Alert type="info" icon="fa-brands fa-empire" classes="w-100">
        {t("Welcome to Trooper Tracker 2.0! To get started, please complete some necessary setup provided below.")}
    </Alert>

    <form onsubmit={vm.submit} novalidate>
        <p class="text-warning">{t("Is your email correct?")}</p>

        <InputContainer>
            <InputText label={t("Email:")} bind:value={vm.form.email} errors={vm.errors.email} />
        </InputContainer>

        <InputContainer>
            <InputText label={t("Legal Name:")} bind:value={vm.form.legal_name} errors={vm.errors.legal_name} />
            <InputHelp>
                {t("Used for official records and communications with event staff and coordinators. This will not be displayed publicly on your profile or on the dashboard, but will be shared with event coordinators for safety and accountability reasons.")}
            </InputHelp>
        </InputContainer>

        <InputContainer>
            <InputSelect
                label={t("Theme:")}
                options={trooper.theme_options}
                bind:value={vm.form.theme}
                errors={vm.errors.theme}
            />
            <InputHelp>{t("Choose your preferred theme for the application interface.")}</InputHelp>
        </InputContainer>

        <InputContainer>
            <InputSelect
                label={t("Notification Frequency:")}
                options={trooper.frequency_options}
                bind:value={vm.form.notification_frequency}
                errors={vm.errors.notification_frequency}
            />
            <InputHelp>
                {t("How often would you like to receive notification emails about events, milestones, and other important updates?")}
            </InputHelp>
        </InputContainer>

        {#if memberships.length > 0}
            <p class="text-warning">{t("Your current organization memberships:")}</p>
            <ul class="list-group mb-3">
                {#each memberships as membership (membership.id)}
                    <li class="list-group-item">
                        <strong>{membership.name}</strong> — {membership.assignment}
                    </li>
                {/each}
            </ul>
        {/if}

        <SubmitButtonContainer>
            <SubmitButton label={t("Update")} submitting={vm.submitting} />
        </SubmitButtonContainer>
    </form>
</SlimCard>
