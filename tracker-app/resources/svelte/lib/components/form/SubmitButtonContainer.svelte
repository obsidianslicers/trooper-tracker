<script lang="ts">
    let { children } = $props();
</script>

<div class="row mt-4">
    <div class="col text-end">
        {@render children?.()}
    </div>
</div>
