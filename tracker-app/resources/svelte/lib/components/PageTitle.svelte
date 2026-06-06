<script lang="ts">
    import pageState from "$lib/states/page-state.svelte";
</script>

<svelte:head>
    <title>{pageState.title ?? ""}</title>
</svelte:head>

{#if pageState.title && pageState.title.length > 0}
    <div>
        <h1 class="text-center py-3 site-title">
            {pageState.title}
        </h1>
    </div>
{/if}
