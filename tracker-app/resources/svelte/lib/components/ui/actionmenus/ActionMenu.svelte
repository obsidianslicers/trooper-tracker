<script lang="ts">
    import type { Snippet } from "svelte";

    interface Props {
        children: Snippet;
    }

    let { children }: Props = $props();
</script>

<div class="dropdown float-end">
    <button
        class="btn btn-outline-secondary dropdown-toggle"
        type="button"
        data-bs-toggle="dropdown"
        aria-label="Actions"
    >
        <i class="fa fa-fw fa-gear pe-3" aria-hidden="true"></i>
    </button>
    <ul class="dropdown-menu">
        {@render children()}
    </ul>
</div>
