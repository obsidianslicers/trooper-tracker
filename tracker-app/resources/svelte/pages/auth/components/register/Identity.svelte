<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputPassword from "$lib/components/form/InputPassword.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import type { RegisterViewModel } from "../../models";

    interface PageProps {
        vm: RegisterViewModel;
    }

    let { vm }: PageProps = $props();
</script>

<InputContainer>
    <InputText
        bind:value={vm.form.email}
        errors={vm.errors.email}
        disabled={vm.useOAuthEmail()}
        label="Email"
    />
</InputContainer>

{#if !vm.useOAuthEmail()}
    <InputContainer>
        <InputPassword
            bind:value={vm.form.password}
            errors={vm.errors.password}
            disabled={vm.useOAuthEmail()}
            label="Password"
        />
    </InputContainer>
{/if}

<InputContainer>
    <InputText
        bind:value={vm.form.legal_name}
        errors={vm.errors.legal_name}
        label="Legal Name"
    />
    <InputHelp>
        Used for official records and communications with event staff and
        coordinators. This will not be displayed on your profile or on the
        dashboard, but will be shared with event coordinators for safety and
        accountability reasons.
    </InputHelp>
</InputContainer>

<InputContainer>
    <InputText
        bind:value={vm.form.display_name}
        errors={vm.errors.display_name}
        label="Display Name"
    />
    <InputHelp>
        Your display name will be shown on your profile and on the dashboard. It
        can be your first name, nickname, or any name you prefer to be
        identified by within the community. This name will be shown publicly on
        your profile and on the dashboard. It should not contain any personally
        identifiable information that you do not wish to share publicly (ie.
        non-authenticated viewers).
    </InputHelp>
</InputContainer>
