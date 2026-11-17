<script lang="ts">
    import type { Snippet } from "svelte";

    interface Props {
        wide?: boolean;
        children?: Snippet;
    }
    let { children, wide }: Props = $props();
</script>

<div
    class="d-flex flex-column align-items-center justify-content-center py-2 m-2"
>
    <div class={wide ? "col-12 col-md-8 col-lg-6" : "col-12 col-md-6 col-lg-4"}>
        {@render children?.()}
    </div>
</div>
