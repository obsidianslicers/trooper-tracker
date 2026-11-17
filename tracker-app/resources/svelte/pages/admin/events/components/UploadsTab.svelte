<script lang="ts">
    import { t } from "$lib/i18n";
    import ConfirmationModal from "$lib/components/ConfirmationModal.svelte";
    import { untrack } from "svelte";
    import { UploadsViewModel, type EventUpload } from "../models";

    interface Props {
        event_id: number;
        uploads: EventUpload[];
        is_administrative: boolean;
    }
    let { event_id, uploads, is_administrative }: Props = $props();

    const vm = new UploadsViewModel(
        untrack(() => event_id),
        untrack(() => is_administrative),
    );

    let file_input: HTMLInputElement | undefined = $state();

    const drop = (e: DragEvent) => {
        e.preventDefault();
        vm.upload(e.dataTransfer?.files ?? null);
    };
</script>

{#if uploads.length === 0}
    <p class="text-muted">
        {is_administrative
            ? t("No administrative photos have been uploaded for this event.")
            : t("No member photos have been uploaded for this event.")}
    </p>
{/if}

<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
    {#each uploads as upload (upload.id)}
        <div class="col" id="upload-{upload.id}">
            <div class="card h-100">
                <img src={upload.small_url} class="card-img-top rounded-top" alt={t("Upload :id", { id: upload.id })} />
                {#if !is_administrative}
                    <div class="card-body p-2">
                        <small class="text-muted d-block">{upload.uploaded_by}</small>
                        {#if upload.tagged.length > 0}
                            <small class="text-muted d-block">
                                {t("Tagged: :names", { names: upload.tagged.join(", ") })}
                            </small>
                        {/if}
                    </div>
                {/if}
                <div class="card-footer p-2">
                    <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-2" onclick={() => vm.ask(upload, "move")}>
                        <i class={["fa me-1", is_administrative ? "fa-share" : "fa-folder"]}></i>
                        {vm.move_label}
                    </button>
                    <button type="button" class="btn btn-sm btn-danger w-100" onclick={() => vm.ask(upload, "delete")}>
                        <i class="fa fa-trash me-1"></i>
                        {t("Delete")}
                    </button>
                </div>
            </div>
        </div>
    {/each}
</div>

{#if is_administrative}
    <button
        type="button"
        class={["event-upload-drop border border-secondary rounded p-5 text-center w-100 mt-3", vm.dragging ? "bg-secondary-subtle" : "bg-light"]}
        onclick={() => file_input?.click()}
        ondragover={(e) => {
            e.preventDefault();
            vm.dragging = true;
        }}
        ondragleave={() => (vm.dragging = false)}
        ondrop={drop}
        disabled={vm.uploading}
    >
        <span class="text-muted">
            {#if vm.uploading}
                <i class="fa fa-fw fa-spinner fa-spin"></i>
                {t("Uploading...")}
            {:else}
                {t("Drag & drop JPG, PNG, WEBP, or HEIC images here, or click to upload")}
            {/if}
        </span>
    </button>
    <input
        bind:this={file_input}
        type="file"
        name="images[]"
        class="d-none"
        multiple
        accept=".jpg,.jpeg,.png,.webp,.heic,.heif"
        onchange={(e) => {
            vm.upload(e.currentTarget.files);
            e.currentTarget.value = "";
        }}
    />
{/if}

<ConfirmationModal
    show={vm.pending !== null}
    title={vm.confirm_prompt.title}
    message={vm.confirm_prompt.message}
    confirmLabel={vm.confirm_prompt.label}
    danger={vm.pending?.action === "delete"}
    onConfirm={vm.confirm}
    onCancel={vm.cancel}
/>
