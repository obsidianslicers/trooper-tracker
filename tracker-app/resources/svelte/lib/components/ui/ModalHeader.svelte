<script lang="ts">
    let { title, canClose = null } = $props();
</script>

<div class="modal-header">
    <h5 class="modal-title">{title}</h5>
    {#if canClose}
        <button
            type="button"
            class="close"
            aria-label="Close"
            onclick={canClose}
        >
            <span aria-hidden="true">&times;</span>
        </button>
    {/if}
</div>
