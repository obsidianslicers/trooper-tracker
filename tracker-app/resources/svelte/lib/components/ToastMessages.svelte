<script lang="ts">
    import { t } from "$lib/i18n";
    import { fade, fly } from "svelte/transition";
    import Alert from "../components/ui/Alert.svelte";
    import type { ToastType } from "../states/toast-state.svelte";
    import toastState from "../states/toast-state.svelte";

    function icon_for(type: ToastType): string {
        if (type === "success") return "fa-check-circle";
        if (type === "danger") return "fa-exclamation-circle";
        if (type === "warning") return "fa-exclamation-triangle";
        return "fa-info-circle";
    }
</script>

<!-- One container: top right on wider screens, along the bottom on phones (app.scss .tt-toasts) -->
<div class="toast-container tt-toasts position-fixed p-3" aria-live="polite" aria-atomic="false">
    {#each toastState.all as message (message.id)}
        <div
            class="toast show"
            role="status"
            aria-live="polite"
            aria-atomic="true"
            in:fly={{ y: -10, duration: 180 }}
            out:fade={{ duration: 180 }}
        >
            <Alert
                type={message.type}
                icon={icon_for(message.type)}
                classes="mb-0 shadow d-flex align-items-center gap-2"
            >
                <span class="flex-grow-1">{message.text}</span>

                {#if message.allow_dismiss}
                    <button
                        type="button"
                        class="btn-close ms-auto"
                        aria-label={t("Dismiss")}
                        onclick={() => toastState.dismiss(message.id)}
                    ></button>
                {/if}
            </Alert>
        </div>
    {/each}
</div>
