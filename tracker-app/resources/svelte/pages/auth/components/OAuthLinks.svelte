<script lang="ts">
    import type { AuthConfiguration } from "../models/types";
    import OAuthLink from "./OAuthLink.svelte";

    interface Props {
        oauth: AuthConfiguration | null;
        action: "login" | "signup";
    }

    let { oauth, action }: Props = $props();
</script>

{#each oauth?.providers ?? [] as provider (provider.key)}
    <OAuthLink {provider} {action} />
{/each}
