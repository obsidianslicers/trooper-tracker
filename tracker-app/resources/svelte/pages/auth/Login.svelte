<script lang="ts">
    import Alert from "$lib/components/ui/Alert.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import { LoginViewModel, type LoginPageData } from "$lib/domains/auth";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import FormPanel from "./components/login/FormPanel.svelte";
    import OAuthPanel from "./components/login/OAuthPanel.svelte";
    import OAuthXenforoLink from "./components/OAuthXenforoLink.svelte";

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
            <OAuthXenforoLink
                configured={vm.oauth?.xenforo.configured}
                name={vm.oauth?.xenforo.name}
                prefix="Login"
            />
        {/if}
    {:else}
        <FormPanel {vm} />
        <OAuthPanel {vm} />
    {/if}
</SlimCard>
