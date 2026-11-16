<script lang="ts">
    import { t } from "$lib/i18n";
    import Alert from "$lib/components/ui/Alert.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import OAuthLinks from "./components/OAuthLinks.svelte";
    import EmailLink from "./components/sign-up/EmailLink.svelte";
    import OAuthPanel from "./components/sign-up/OAuthPanel.svelte";
    import { SignUpViewModel, type SignUpPageData } from "./models";

    const page = usePage<SignUpPageData>();

    pageState.title = t("Sign Up");

    let vm = new SignUpViewModel(page.props);
</script>

<SlimCard>
    {#if vm.oauth?.required}
        <Alert>
            {t("You must use :provider to sign up.", { provider: vm.oauth.required.name })}
        </Alert>
        <OAuthLinks oauth={vm.oauth} action="signup" />
    {:else}
        <EmailLink />
        <OAuthPanel {vm} />
    {/if}
</SlimCard>
