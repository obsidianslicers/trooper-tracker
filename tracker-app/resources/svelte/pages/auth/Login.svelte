<script lang="ts">
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputPassword from "$lib/components/form/InputPassword.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import Alert from "$lib/components/ui/Alert.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import type { LoginPageData } from "$lib/domains/auth";
    import { LoginViewModel } from "$lib/domains/auth";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import OAuthLink from "./components/OAuthLink.svelte";

    const page = usePage<LoginPageData>();

    pageState.title = "Login";

    let vm = new LoginViewModel(page.props);
</script>

<SlimCard>
    {#if vm.oauth?.xenforo.required}
        {#if vm.oauth?.xenforo.configured === false}
            <Alert type="danger" icon="fa-circle-xmark">
                XenForo OAuth is required, but it is not configured. Please
                contact an administrator.
            </Alert>
        {:else}
            <Alert>
                You must use {vm.oauth?.xenforo.name} to login.
            </Alert>
        {/if}
    {:else}
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

        {#if vm.oauth?.xenforo.configured || vm.oauth?.google.enabled}
            <hr class="my-5" />

            <div class="d-grid gap-2 mb-3">
                <OAuthLink
                    configured={vm.oauth?.xenforo.configured}
                    name={vm.oauth?.xenforo.name}
                    imageUrl="https://xenforo.com/community/styles/default/xenforo/xenforo-favicon.png"
                    url={getRoute("auth.oauth-redirect", {
                        provider: "xenforo",
                    })}
                />
                <OAuthLink
                    configured={vm.oauth?.google.configured}
                    name="Google"
                    imageUrl="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                    url={getRoute("auth.oauth-redirect", {
                        provider: "google",
                    })}
                />
            </div>
        {/if}
    {/if}
</SlimCard>
