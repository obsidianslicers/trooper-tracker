<script lang="ts">
    let { header = null, footer = null, children } = $props();
</script>

<div class="d-flex flex-column align-items-center justify-content-center py-2">
    <div class="col-lg-8 col-md-9 col-sm-12 m-auto mb-5 p-0 mt-4">
        <div class={["card border-1"]}>
            {#if header}
                <div class={["card-header"]}>
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
