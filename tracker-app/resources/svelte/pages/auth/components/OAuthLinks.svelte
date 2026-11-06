<script lang="ts">
    import type { AuthConfiguration } from "../models/types";
    import OAuthLink from "./OAuthLink.svelte";

    interface Props {
        oauth: AuthConfiguration | null;
        prefix: string;
    }

    let { oauth, prefix }: Props = $props();
</script>

{#each oauth?.providers ?? [] as provider (provider.key)}
    <OAuthLink {provider} {prefix} />
{/each}
