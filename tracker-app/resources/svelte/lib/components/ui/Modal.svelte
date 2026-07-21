<script lang="ts">
    import { Modal as BoostrapModel } from 'bootstrap';
    import { onDestroy, onMount } from 'svelte';

    let {
        show = false,
        children,
        fullscreen = false,
        title,
        canClose,
    } = $props();

    let modalElement: HTMLDivElement;
    let modalInstance: BoostrapModel;

    onMount(() => {
        modalInstance = new BoostrapModel(modalElement);
        if (show) {
            modalInstance.show();
        }
    });

    onDestroy(() => {
        if (modalInstance) {
            modalInstance.dispose();
        }
    });

    $effect(() => {
        if (show && modalInstance) {
            modalInstance.show();
        } else if (modalInstance) {
            modalInstance.hide();
        }
    });
</script>

<div bind:this={modalElement} class="modal" tabindex="-1" role="dialog">
    <div
        class={['modal-dialog', { 'modal-fullscreen': fullscreen }]}
        role="document"
    >
        <div class="modal-content">
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
            <div class="modal-body">
                {@render children?.()}
            </div>
        </div>
    </div>
</div>
