<script lang="ts">
    import { t } from "$lib/i18n";
    import type { DisplayUploads, EventDisplayViewModel } from "../../models";
    import PhotoModal from "./PhotoModal.svelte";
    import SectionTitle from "./SectionTitle.svelte";
    import UploadGrid from "./UploadGrid.svelte";
    import UploadZone from "./UploadZone.svelte";

    interface Props {
        vm: EventDisplayViewModel;
        uploads: DisplayUploads;
        default_instructional: boolean;
    }

    let { vm, uploads, default_instructional }: Props = $props();

    let viewing: string | null = $state(null);
</script>

<div id="event-uploads-panel">
    {#if uploads.instructional.length > 0}
        <div class="mt-5" id="admin-event-uploads">
            <SectionTitle>{t("Instructional Uploads")}</SectionTitle>
            <UploadGrid {vm} uploads={uploads.instructional} is_instructional
                        onview={(upload) => (viewing = upload.large_url)} />
        </div>
    {/if}

    <div class="mt-5" id="event-uploads">
        <SectionTitle>{t("Mission Review")}</SectionTitle>
        <UploadGrid {vm} uploads={uploads.mission_review} is_instructional={false}
                    onview={(upload) => (viewing = upload.large_url)} />
    </div>
</div>

<UploadZone {vm} url={uploads.upload_url} {default_instructional} />

<PhotoModal bind:url={viewing} />
