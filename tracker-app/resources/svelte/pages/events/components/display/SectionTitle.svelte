<script lang="ts">
    import type { Snippet } from "svelte";

    let { children, classes = "" }: { children: Snippet; classes?: string } = $props();
</script>

<h6 class={["text-uppercase text-muted", classes]}>{@render children()}</h6>
