<script lang="ts">
    import Alert from "$lib/components/ui/Alert.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import OAuthLinks from "./components/OAuthLinks.svelte";
    import EmailLink from "./components/sign-up/EmailLink.svelte";
    import OAuthPanel from "./components/sign-up/OAuthPanel.svelte";
    import { SignUpViewModel, type SignUpPageData } from "./models";

    const page = usePage<SignUpPageData>();

    pageState.title = "Sign Up";

    let vm = new SignUpViewModel(page.props);
</script>

<SlimCard>
    {#if vm.oauth?.required}
        <Alert>
            You must use {vm.oauth.required.name} to sign up.
        </Alert>
        <OAuthLinks oauth={vm.oauth} prefix="Sign Up" />
    {:else}
        <EmailLink />
        <OAuthPanel {vm} />
    {/if}
</SlimCard>
