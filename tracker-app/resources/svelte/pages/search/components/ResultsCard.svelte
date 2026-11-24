<!-- One kind of result (troopers or events): a count, the rows, or a "none found" line. -->
<script lang="ts">
    import type { Snippet } from "svelte";

    interface Props {
        title: string;
        icon: string;
        count: number;
        empty: string;
        /** Shown under the rows when the search hit the limit. */
        more: string | null;
        children: Snippet;
    }
    let { title, icon, count, empty, more, children }: Props = $props();
</script>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white text-uppercase small fw-bold">
        <i class="fa-solid {icon} me-2"></i>
        {title}
        <span class="badge bg-secondary ms-2">{count}</span>
    </div>
    {#if count === 0}
        <div class="card-body text-muted">{empty}</div>
    {:else}
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                {@render children()}
            </table>
        </div>
        {#if more}
            <div class="card-footer text-muted small">
                {@html more}
            </div>
        {/if}
    {/if}
</div>
