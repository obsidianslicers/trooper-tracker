<script lang="ts">
    import { t } from "$lib/i18n";
    import type { DisplayUpload, EventDisplayViewModel } from "../../models";
    import ShareLinks from "./ShareLinks.svelte";

    interface Props {
        vm: EventDisplayViewModel;
        uploads: DisplayUpload[];
        is_instructional: boolean;
        onview: (upload: DisplayUpload) => void;
    }

    let { vm, uploads, is_instructional, onview }: Props = $props();

    const UPLOAD_PROPS = ["uploads", "toast", "flash", "errors"];

    const toggleTag = (upload: DisplayUpload) => vm.send(upload.tag_url!, {}, { only: UPLOAD_PROPS });

    const move = (upload: DisplayUpload) => vm.confirm({
        title: is_instructional ? t("Move to Mission Review") : t("Move to Instructional"),
        message: is_instructional
            ? t("Move this photo to Mission Review? Everyone will see it with the troop photos.")
            : t("Move this photo to Instructional? It will show with the maps and directions, not the troop photos."),
        label: is_instructional ? t("Move to Mission Review") : t("Move to Instructional"),
        danger: false,
        run: () => vm.send(upload.move_url!, {}, { only: UPLOAD_PROPS }),
    });
</script>

<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
    {#each uploads as upload (upload.id)}
        <div class="col">
            <div class="card h-100 position-relative event-image-share-wrapper">
                <a href={upload.large_url} class="d-block event-upload-photo-trigger" onclick={(e) => {
                    e.preventDefault();
                    onview(upload);
                }}>
                    <img src={upload.small_url} class="card-img-top rounded" alt={t("Image #:id", { id: upload.id })} />
                </a>
                {#if is_instructional && upload.uploaded_by}
                    <div class="card-body p-2">
                        <small class="text-muted">{t("Uploaded by :name", { name: upload.uploaded_by })}</small>
                    </div>
                {/if}
                <div class="event-image-share-buttons">
                    <ShareLinks links={upload.shares} />

                    {#if upload.tag_url}
                        <div class="mt-2">
                            <button type="button" class={["btn btn-sm", upload.is_tagged ? "btn-warning" : "btn-outline-light"]}
                                    disabled={vm.sending} onclick={() => toggleTag(upload)}>
                                <i class={["fa fa-fw me-1", upload.is_tagged ? "fa-user-times" : "fa-user-plus"]}></i>
                                {upload.is_tagged ? t("Untag me") : t("Tag me")}
                            </button>
                        </div>
                    {/if}

                    {#if upload.move_url}
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-light" disabled={vm.sending}
                                    onclick={() => move(upload)}>
                                <i class={["fa fa-fw me-1", is_instructional ? "fa-camera" : "fa-map"]}></i>
                                {is_instructional ? t("Move to Mission Review") : t("Move to Instructional")}
                            </button>
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    {/each}
</div>
