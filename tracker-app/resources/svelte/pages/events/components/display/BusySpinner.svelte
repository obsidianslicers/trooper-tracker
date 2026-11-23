<!--
    Inside a button, while the request it sent is on its way (EventDisplayViewModel.isBusy).
-->
<script lang="ts">
    let { busy }: { busy: boolean } = $props();
</script>

{#if busy}
    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
{/if}
