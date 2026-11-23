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
    // Where Bootstrap is in its show/hide cycle, so the modal can be put away cleanly when its
    // owner removes it mid-fade (a picker closing as it is swapped for the next step).
    let phase: "hidden" | "showing" | "shown" | "hiding" = "hidden";
    const trackPhase = (event: Event) => {
        phase = {
            "show.bs.modal": "showing",
            "shown.bs.modal": "shown",
            "hide.bs.modal": "hiding",
            "hidden.bs.modal": "hidden",
        }[event.type] as typeof phase;
    };
    const PHASE_EVENTS = ["show.bs.modal", "shown.bs.modal", "hide.bs.modal", "hidden.bs.modal"];

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

        PHASE_EVENTS.forEach((name) => modalElement.addEventListener(name, trackPhase));
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

    // Disposing mid-fade leaves Bootstrap's pending fade callback with no element (it throws) and
    // leaves the backdrop and the page's scroll lock behind. Close it first, then dispose.
    onDestroy(() => {
        if (!modalInstance) {
            return;
        }

        const instance = modalInstance;
        if (phase === "hidden") {
            instance.dispose();
            return;
        }

        modalElement.addEventListener("hidden.bs.modal", () => instance.dispose(), { once: true });
        if (phase === "showing") {
            modalElement.addEventListener("shown.bs.modal", () => instance.hide(), { once: true });
        } else {
            instance.hide();
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
