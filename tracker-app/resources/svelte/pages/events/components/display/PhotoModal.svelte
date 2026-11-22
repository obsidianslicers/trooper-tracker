<script lang="ts">
    import { t } from "$lib/i18n";
    import { Modal } from "bootstrap";
    import { onDestroy, onMount } from "svelte";

    let { url = $bindable(null) }: { url: string | null } = $props();

    let element: HTMLDivElement;
    let modal: Modal | undefined;

    onMount(() => {
        modal = new Modal(element);
        element.addEventListener("hidden.bs.modal", () => (url = null));
    });

    onDestroy(() => modal?.dispose());

    $effect(() => {
        if (url) {
            modal?.show();
        } else {
            modal?.hide();
        }
    });
</script>

<div bind:this={element} class="modal fade" id="eventUploadPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
        <div class="modal-content bg-dark border-0">
            <div class="modal-body p-0 position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                        aria-label={t("Close")} onclick={() => (url = null)}></button>
                <img src={url ?? ""} alt={t("Event photo")} class="img-fluid w-100" />
            </div>
        </div>
    </div>
</div>
