<script lang="ts">
    interface Props {
        configured: boolean;
        url: string;
        name: string;
        imageUrl: string;
    }
    let { configured, url, name, imageUrl }: Props = $props();
</script>

{#if configured}
    <a
        href={url}
        class="btn btn-outline-secondary w-100 d-flex justify-content-center align-items-center gap-2"
    >
        <img
            src={imageUrl}
            alt="{name} Logo"
            style="width: 18px; height: 18px;"
            class="me-3"
        />
        <span>Login with {name}</span>
    </a>
{/if}
