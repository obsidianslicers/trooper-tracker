<script lang="ts">
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputPassword from "$lib/components/form/InputPassword.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import { LoginViewModel } from "../../models";

    interface Props {
        vm: LoginViewModel;
    }

    let { vm }: Props = $props();
</script>

{#if vm.oauth?.email_password.enabled}
    <form onsubmit={vm.submit}>
        <InputContainer>
            <InputText
                bind:value={vm.form.email}
                errors={vm.errors.email}
                label="Email"
            />
        </InputContainer>

        <InputContainer>
            <InputPassword
                bind:value={vm.form.password}
                errors={vm.errors.password}
                label="Password"
            />
        </InputContainer>

        <InputContainer>
            <InputCheckbox
                bind:checked={vm.form.remember_me}
                label="Keep me logged in"
                value="Y"
            />
        </InputContainer>

        <SubmitButtonContainer>
            <SubmitButton
                label="Login"
                submitting={vm.submitting}
                disabled={!vm.dirty}
            />
        </SubmitButtonContainer>
    </form>
{/if}
