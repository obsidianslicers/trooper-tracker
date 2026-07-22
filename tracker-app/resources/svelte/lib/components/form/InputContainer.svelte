<script lang="ts">
    let { children, classes = "" } = $props();
</script>

<div class="mb-3 {classes.trim()}">
    {@render children?.()}
</div>
