<script lang="ts">
    import Alert from "../Alert.svelte";

    interface Props {
        label: string;
        href?: string | null;
        icon?: string | null;
        click?: (() => void) | null;
    }

    let {
        label = "",
        href = null,
        icon = null,
        click = null,
    }: Props = $props();
</script>

{#snippet display(icon: string | null, label: string | null)}
    {#if icon && icon.length > 0}
        <i class={["fa fa-fw", icon, "me-3"]}></i>
    {/if}
    {#if label && label.length > 0}
        {label}
    {/if}
{/snippet}

<li>
    {#if click}
        <button onclick={click} class="dropdown-item">
            {@render display(icon, label)}
        </button>
    {:else if href}
        <a class="dropdown-item" {href}>
            {@render display(icon, label)}
        </a>
    {:else}
        <Alert type="danger">
            Action Menu Item component requires either a click handler or href.
        </Alert>
    {/if}
</li>
