<script lang="ts">
    interface Props {
        errors?: string | string[];
    }
    let { errors = [] }: Props = $props();
</script>

{#snippet error_text(message: string)}
    <div class="form-text text-danger px-2">{message}</div>
{/snippet}

{#if errors && errors.length > 0}
    {#if Array.isArray(errors)}
        {#each errors as error, i (i)}
            {@render error_text(error)}
        {/each}
    {:else}
        {@render error_text(errors)}
    {/if}
{/if}
