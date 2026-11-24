<script lang="ts">
    import type { Snippet } from "svelte";

    let { title, icon, children }: { title: string; icon: string; children?: Snippet } = $props();
</script>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <h2 class="h4 mb-0 text-uppercase fw-bold text-white">
        <i class="{icon} me-2"></i>
        {title}
    </h2>
    {#if children}
        <div class="align-self-start align-self-sm-auto">
            {@render children()}
        </div>
    {/if}
</div>
