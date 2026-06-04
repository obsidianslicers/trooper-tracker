<script lang="ts">
    let { children, classes = '' } = $props();
</script>

<div class={`mb-2 ${classes}`.trim()}>
    {@render children?.()}
</div>
