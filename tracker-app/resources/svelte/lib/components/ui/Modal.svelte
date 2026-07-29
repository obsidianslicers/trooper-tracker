<script lang="ts">
    import { Modal as BoostrapModal } from "bootstrap";
    import { onDestroy, onMount } from "svelte";

    let {
        show = $bindable(false),
        children,
        fullscreen = false,
        title,
        canClose,
    } = $props();

    let modalElement: HTMLDivElement;
    let modalInstance: BoostrapModal;

    onMount(() => {
        modalInstance = new BoostrapModal(modalElement);

        // 1. Listen for Bootstrap's hide event (backdrop click, ESC key, etc.)
        const handleHidden = () => {
            if (show) {
                show = false; // Sync state back to Svelte
                canClose?.(); // Call cleanup (e.g. vm.closeModal())
            }
        };

        modalElement.addEventListener("hidden.bs.modal", handleHidden);

        if (show) {
            modalInstance.show();
        }

        return () => {
            modalElement.removeEventListener("hidden.bs.modal", handleHidden);
        };
    });

    onDestroy(() => {
        if (modalInstance) {
            modalInstance.dispose();
        }
    });

    $effect(() => {
        if (!modalInstance) {
            return;
        }

        if (show) {
            modalInstance.show();
        } else {
            modalInstance.hide();
        }
    });
</script>

<div
    bind:this={modalElement}
    class="modal fade"
    tabindex="-1"
    aria-hidden="true"
>
    <div class={["modal-dialog", { "modal-fullscreen": fullscreen }]}>
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{title}</h5>
                {#if canClose}
                    <button
                        type="button"
                        class="btn-close"
                        aria-label="Close"
                        onclick={canClose}
                    ></button>
                {/if}
            </div>
            <div class="modal-body">
                {@render children?.()}
            </div>
        </div>
    </div>
</div>
