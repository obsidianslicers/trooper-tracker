<script>
    import configStateSvelte from '$lib/states/config-state.svelte';
    import ProviderLoginLink from './ProviderLoginLink.svelte';
    let { prefix = 'Login' } = $props();
</script>

<ProviderLoginLink
    label="{prefix} with Xenforo"
    show={configStateSvelte.xenForoOauthEnabled}
    url={configStateSvelte.xenForoOauthUrl}
/>
