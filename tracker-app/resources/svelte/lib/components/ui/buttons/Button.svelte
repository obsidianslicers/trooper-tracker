<script lang="ts">
    import Alert from '../Alert.svelte';

    interface Props {
        btnclass?: string | null;
        submit?: boolean | null;
        href?: string | null;
        label?: string | null;
        icon?: string | null;
        disabled?: boolean | null;
        click?: (() => void) | null;
    }

    let {
        btnclass = 'btn-primary',
        submit = false,
        disabled = false,
        href = null,
        icon = null,
        click = null,
        label = 'Create',
    }: Props = $props();
</script>

{#snippet display(icon: string | null, label: string | null)}
    {#if icon && icon.length > 0}
        <i class={['fa fa-fw', icon]}></i>
    {/if}
    {#if label && label.length > 0}
        {label}
    {/if}
{/snippet}

{#if click}
    <button onclick={click} class={['btn', btnclass]} {disabled}>
        {@render display(icon, label)}
    </button>
{:else if href}
    <a {href} class={['btn', btnclass]}>
        {@render display(icon, label)}
    </a>
{:else if submit}
    <button type="submit" class={['btn', btnclass]} {disabled}>
        {@render display(icon, label)}
    </button>
{:else}
    <Alert type="danger">
        Button component requires either a click handler, href, or submit prop.
    </Alert>
{/if}
