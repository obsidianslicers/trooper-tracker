<script lang="ts">
    let { errors = [] } = $props();
</script>

{#if errors && errors.length > 0}
    {#each errors as error, i (i)}
        <span class="form-text text-danger ps-2">{error}</span>
    {/each}
{/if}
