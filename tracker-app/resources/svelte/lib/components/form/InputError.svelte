<script lang="ts">
    interface Props {
        errors?: string | string[];
    }
    let { errors = [] }: Props = $props();
</script>

{#if errors && errors.length > 0}
    {#if Array.isArray(errors)}
        {#each errors as error, i (i)}
            <span class="form-text text-danger ps-2">{error}</span>
        {/each}
    {:else}
        <span class="form-text text-danger ps-2">{errors}</span>
    {/if}
{/if}
