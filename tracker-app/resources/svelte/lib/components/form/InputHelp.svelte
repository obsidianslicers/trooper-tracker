<script lang="ts">
    let { children } = $props();
</script>

<div class="form-text text-muted p-2">
    {@render children?.()}
</div>
