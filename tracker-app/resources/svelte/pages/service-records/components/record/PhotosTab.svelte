<!-- Photos the trooper is tagged in; a click shows one large. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { route } from "ziggy-js";
    import PhotoModal from "$lib/components/PhotoModal.svelte";
    import type { TaggedUpload } from "../../models/record-types";

    let { uploads, isSelf }: { uploads: TaggedUpload[]; isSelf: boolean } = $props();

    let viewing: string | null = $state(null);

    const show = (event: MouseEvent, url: string) => {
        event.preventDefault();
        viewing = url;
    };

    const galleryLink = $derived(
        `<a href="${route("service-records.photo-gallery")}">${t("photo gallery")}</a>`,
    );
</script>

{#if uploads.length === 0}
    <div class="text-center text-muted py-5">
        <i class="fa-solid fa-camera fa-3x mb-3 d-block"></i>
        {#if isSelf}
            <p class="mb-2">{t("You don't have any tagged photos yet.")}</p>
            <p class="mb-0">
                {@html t(
                    'Find yourself in a photo in the :gallery, open its event, and click <span class="fw-bold">"Tag me"</span> to add it here.',
                    { gallery: galleryLink },
                )}
            </p>
        {:else}
            <p class="mb-0">{t("No tagged photos yet.")}</p>
        {/if}
    </div>
{:else}
    <div class="row g-3">
        {#each uploads as upload (upload.id)}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100">
                    <div class="ratio ratio-1x1">
                        <a href={upload.large_url} class="d-block w-100 h-100" onclick={(e) => show(e, upload.large_url)}>
                            <img src={upload.small_url} class="img-fluid photo" alt={t("Service record photo")} />
                        </a>
                    </div>
                </div>
            </div>
        {/each}
    </div>
{/if}

<PhotoModal bind:url={viewing} alt={t("Service record photo")} />

<style>
    .photo {
        object-fit: cover;
        width: 100%;
        height: 100%;
    }
</style>
