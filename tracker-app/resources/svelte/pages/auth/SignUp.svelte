<script lang="ts">
    import Alert from "$lib/components/ui/Alert.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import { SignUpViewModel, type SignUpPageData } from "$lib/domains/auth";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import OAuthXenforoLink from "./components/OAuthXenforoLink.svelte";
    import EmailLink from "./components/sign-up/EmailLink.svelte";
    import OAuthPanel from "./components/sign-up/OAuthPanel.svelte";

    const page = usePage<SignUpPageData>();

    pageState.title = "Sign Up";

    let vm = new SignUpViewModel(page.props);
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
            <OAuthXenforoLink
                configured={vm.oauth?.xenforo.configured}
                name={vm.oauth?.xenforo.name}
                prefix="Sign Up"
            />
        {/if}
    {:else}
        <EmailLink />
        <OAuthPanel {vm} />
    {/if}
</SlimCard>
