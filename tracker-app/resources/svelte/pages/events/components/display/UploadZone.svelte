<!-- Upload as Instructional or Troop photo (defaulting by whether the event has started), then drop or pick images. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { untrack } from "svelte";
    import type { EventDisplayViewModel } from "../../models";

    interface Props {
        vm: EventDisplayViewModel;
        url: string;
        default_instructional: boolean;
    }

    let { vm, url, default_instructional }: Props = $props();

    let upload_type = $state(untrack(() => (default_instructional ? "instructional" : "troop")));
    let dragging = $state(false);
    let uploading = $state(false);
    let input: HTMLInputElement | undefined = $state();

    const upload = (files: FileList | null | undefined) => {
        dragging = false;

        if (!files || files.length === 0) {
            return;
        }

        uploading = true;
        vm.send(url, { images: Array.from(files), upload_type }, {
            files: true,
            only: ["uploads", "toast", "flash", "errors"],
        });
    };

    //  done once the page has the new photos (or the error)
    $effect(() => {
        if (!vm.sending) {
            uploading = false;
        }
    });
</script>

<div class="mt-3">
    <hr />

    {#if uploading}
        <div class="container-fluid p-0 m-0 mb-1" role="progressbar" aria-label={t("Uploading...")}>
            <div class="progress rounded-0">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
        </div>
    {/if}

    <fieldset class="mb-2">
        <legend class="form-label fs-6 mb-1">{t("Upload as")}</legend>
        <div class="btn-group flex-wrap" role="group">
            <input type="radio" class="btn-check" name="upload_type" id="upload-type-instructional"
                   value="instructional" autocomplete="off" bind:group={upload_type} />
            <label class="btn btn-outline-primary" for="upload-type-instructional">
                <i class="fa fa-fw fa-map me-1"></i>{t("Instructional (maps, parking, directions)")}
            </label>
            <input type="radio" class="btn-check" name="upload_type" id="upload-type-troop"
                   value="troop" autocomplete="off" bind:group={upload_type} />
            <label class="btn btn-outline-primary" for="upload-type-troop">
                <i class="fa fa-fw fa-camera me-1"></i>{t("Troop photo")}
            </label>
        </div>
    </fieldset>

    <button type="button"
            class={["upload-zone border border-secondary rounded p-5 text-center w-100", dragging ? "bg-white" : "bg-light"]}
            disabled={uploading}
            onclick={() => input?.click()}
            ondragover={(e) => {
                e.preventDefault();
                dragging = true;
            }}
            ondragleave={() => (dragging = false)}
            ondrop={(e) => {
                e.preventDefault();
                upload(e.dataTransfer?.files);
            }}>
        <span id="upload-label" class="text-muted">
            {#if uploading}
                <i class="fa fa-fw fa-spinner fa-spin"></i>
                {t("Uploading...")}
            {:else}
                {t("Drag & drop JPG, PNG, WEBP, or HEIC images here, or click to upload")}
            {/if}
        </span>
    </button>
    <input bind:this={input} type="file" name="images[]" class="image-input d-none" multiple
           accept=".jpg,.jpeg,.png,.webp,.heic,.heif"
           onchange={(e) => {
               upload(e.currentTarget.files);
               e.currentTarget.value = "";
           }} />
</div>
