<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
</script>

<svelte:head>
    <title>{pageState.title ?? "Troop Tracker"}</title>
</svelte:head>

{#if pageState.title && pageState.title.length > 0}
    <div>
        <h1 class="text-center py-3 site-title">
            {pageState.title}
        </h1>
    </div>
{:else}
    <div>
        <h1 class="text-center py-3 site-title">
            {pageState.appName}
            {#if pageState.appName != "Troop Tracker"}
                <br />
                {t("Troop Tracker")}
            {/if}
        </h1>
    </div>
{/if}
