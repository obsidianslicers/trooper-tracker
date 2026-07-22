<script lang="ts">
    import type { Snippet } from 'svelte';

    interface Props {
        children?: Snippet;
    }
    let { children }: Props = $props();
</script>

<div
    class="d-flex flex-column align-items-center justify-content-center py-2 m-2"
>
    <div class="col-12 col-md-8 col-lg-6">
        {@render children?.()}
    </div>
</div>
