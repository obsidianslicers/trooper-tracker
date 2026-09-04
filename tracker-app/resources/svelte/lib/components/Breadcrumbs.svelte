<script lang="ts">
    import breadCrumbState from "$lib/states/bread-crumb-state.svelte";
    import { Link } from "@inertiajs/svelte";
</script>

{#if breadCrumbState.exists}
    <div class="container-fluid">
        <nav class="mt-1">
            <ol class="breadcrumb border border-0">
                {#each breadCrumbState.crumbs as crumb (crumb.id)}
                    <li class="breadcrumb-item">
                        {#if crumb.url}
                            <Link href={crumb.url}>{crumb.title}</Link>
                        {:else}
                            {crumb.title}
                        {/if}
                    </li>
                {/each}
            </ol>
        </nav>
    </div>
{/if}
