<script lang="ts">
    import OAuthLink from "./OAuthLink.svelte";

    interface Props {
        configured: boolean;
        name: string;
        prefix: string;
    }

    let { configured, prefix, name = "Xenforo" }: Props = $props();
</script>

<OAuthLink
    imageUrl="https://xenforo.com/community/styles/default/xenforo/xenforo-favicon.png"
    provider="xenforo"
    {configured}
    {prefix}
    {name}
/>
