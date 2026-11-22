<script lang="ts">
    import { t } from "$lib/i18n";
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
    // Bootstrap ignores show() and hide() while the modal is still fading, so a quick confirm (or
    // a second confirm right after one) would leave it stuck. Catch up once the fade ends.
    let hiding = false;

    onMount(() => {
        modalInstance = new BoostrapModal(modalElement);

        const handleShown = () => {
            if (!show) {
                hide();
            }
        };

        const handleHidden = () => {
            if (hiding) {
                hiding = false;
                if (show) {
                    modalInstance.show();
                }
                return;
            }
            // closed by Bootstrap itself (backdrop click, ESC key, etc.)
            if (show) {
                show = false;
                canClose?.();
            }
        };

        modalElement.addEventListener("shown.bs.modal", handleShown);
        modalElement.addEventListener("hidden.bs.modal", handleHidden);

        if (show) {
            modalInstance.show();
        }

        return () => {
            modalElement.removeEventListener("shown.bs.modal", handleShown);
            modalElement.removeEventListener("hidden.bs.modal", handleHidden);
        };
    });

    onDestroy(() => {
        if (modalInstance) {
            modalInstance.dispose();
        }
    });

    const hide = () => {
        if (modalElement.classList.contains("show")) {
            hiding = true;
        }
        modalInstance.hide();
    };

    $effect(() => {
        if (!modalInstance) {
            return;
        }

        if (show) {
            modalInstance.show();
        } else {
            hide();
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
                        aria-label={t("Close")}
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
