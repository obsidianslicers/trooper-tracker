<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputDate from "$lib/components/form/InputDate.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import type { RegisterViewModel } from "$lib/domains/auth";

    interface PageProps {
        vm: RegisterViewModel;
        appName: string;
    }

    let { vm, appName }: PageProps = $props();
</script>

{#if vm.requiresGuardian()}
    <InputContainer>
        <InputDate
            bind:value={vm.inputs.date_of_birth}
            errors={vm.errors.date_of_birth}
            label="Date of Birth"
        />
        <InputHelp>
            You must be at least 13 years old to register for a Galactic Academy
            account. Your date of birth will not be displayed publicly on your
            profile or on the dashboard.
        </InputHelp>
    </InputContainer>
    <InputContainer>
        <InputText
            bind:value={vm.inputs.guardian_email}
            errors={vm.errors.guardian_email}
            label="Guardian Email"
        />
        <InputHelp>
            The guardian email must be associated with an existing account
            registered with {appName} to use this email address. Your guardian will
            receive an email notification when you register.
        </InputHelp>
    </InputContainer>
{/if}
