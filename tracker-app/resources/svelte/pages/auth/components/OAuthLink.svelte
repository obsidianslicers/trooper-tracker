<script lang="ts">
    import { getRoute } from "$lib/utils";

    interface Props {
        prefix?: string;
        configured: boolean;
        provider: "xenforo" | "google";
        name: string;
        imageUrl: string;
    }
    let {
        prefix = "Login",
        configured,
        provider,
        name,
        imageUrl,
    }: Props = $props();
</script>

{#if configured}
    <a
        href={getRoute("auth.oauth-redirect", { provider })}
        class="btn btn-outline-secondary w-100 d-flex justify-content-center align-items-center gap-2"
    >
        <img
            src={imageUrl}
            alt="{name} Logo"
            style="width: 18px; height: 18px;"
            class="me-3"
        />
        <span>{prefix} with {name}</span>
    </a>
{/if}
