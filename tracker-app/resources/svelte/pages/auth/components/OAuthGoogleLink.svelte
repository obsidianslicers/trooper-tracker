<script lang="ts">
    import OAuthLink from "./OAuthLink.svelte";

    interface Props {
        configured: boolean;
        prefix: string;
    }

    let { configured, prefix }: Props = $props();
</script>

<OAuthLink
    imageUrl="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
    provider="google"
    name="Google"
    {configured}
    {prefix}
/>
