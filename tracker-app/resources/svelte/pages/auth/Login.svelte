<script lang="ts">
    import { t } from "$lib/i18n";
    import Alert from "$lib/components/ui/Alert.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import FormPanel from "./components/login/FormPanel.svelte";
    import OAuthPanel from "./components/login/OAuthPanel.svelte";
    import OAuthLinks from "./components/OAuthLinks.svelte";
    import { LoginViewModel, type LoginPageData } from "./models";

    const page = usePage<LoginPageData>();

    pageState.title = t("Login");

    let vm = new LoginViewModel(page.props);
</script>

<SlimCard>
    {#if vm.oauth?.required}
        <Alert>
            {t("You must use :provider to login.", { provider: vm.oauth.required.name })}
        </Alert>
        <OAuthLinks oauth={vm.oauth} action="login" />
    {:else}
        <FormPanel {vm} />
        <OAuthPanel {vm} />
    {/if}
</SlimCard>
