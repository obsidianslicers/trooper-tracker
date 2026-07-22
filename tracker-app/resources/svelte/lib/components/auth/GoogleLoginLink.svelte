<script>
    import configStateSvelte from '$lib/states/config-state.svelte';
    import ProviderLoginLink from './ProviderLoginLink.svelte';
    let { prefix = 'Login' } = $props();
</script>

<ProviderLoginLink
    label="{prefix} with Google"
    show={configStateSvelte.googleOauthEnabled}
    url={configStateSvelte.googleOauthUrl}
/>
