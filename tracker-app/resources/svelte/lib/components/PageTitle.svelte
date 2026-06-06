<script lang="ts">
    import pageState from "$lib/states/page-state.svelte";
</script>

<svelte:head>
    <title>{pageState.title ?? ""}</title>
</svelte:head>

<div>
    <h1 class="text-center py-2">
        {pageState.title}
    </h1>
</div>
