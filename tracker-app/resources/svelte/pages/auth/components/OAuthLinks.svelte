<script lang="ts">
    import type { SignUpOptions } from "../models/types";
    import OAuthLink from "./OAuthLink.svelte";

    interface Props {
        oauth: SignUpOptions | null;
        action: "login" | "signup";
    }

    let { oauth, action }: Props = $props();
</script>

{#each oauth?.providers ?? [] as provider (provider.key)}
    <OAuthLink {provider} {action} />
{/each}
