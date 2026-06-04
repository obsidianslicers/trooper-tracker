<script lang="ts">
    let { header = null, footer = null, children } = $props();
</script>

<div class="d-flex flex-column align-items-center justify-content-center py-2">
    <div class="col-12 col-md-8 col-lg-4 p-1">
        <div class={['card border-1']}>
            {#if header}
                <div class={['card-header']}>
                    <h1>{@render header?.()}</h1>
                </div>
            {/if}
            <div class="card-body">
                {@render children?.()}
            </div>
            {#if footer}
                <div class="card-footer">
                    {@render footer?.()}
                </div>
            {/if}
        </div>
    </div>
</div>
