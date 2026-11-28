<!--
    Plain text a member typed, with its web addresses as links that open in a new tab. The
    text is never treated as HTML. Line breaks are the parent's to keep (white-space: pre-line).
-->
<script lang="ts">
    import { splitLinks } from "$lib/links";

    let { text }: { text: string } = $props();

    const parts = $derived(splitLinks(text));
</script>

{#each parts as part, i (i)}{#if part.url}<a href={part.url} target="_blank" rel="noopener noreferrer">{part.text}</a>{:else}{part.text}{/if}{/each}
