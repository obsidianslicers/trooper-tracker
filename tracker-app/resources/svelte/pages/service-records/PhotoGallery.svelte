<!-- The latest event photos, a page at a time; a click shows one large. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import PhotoModal from "$lib/components/PhotoModal.svelte";
    import Pagination from "$lib/components/ui/Pagination.svelte";
    import ListHeader from "./components/lists/ListHeader.svelte";
    import type { PhotoGalleryPageData } from "./models/list-types";

    let { photos }: PhotoGalleryPageData = $props();

    pageState.title = t("Photo Gallery");

    let viewing: string | null = $state(null);

    const show = (event: MouseEvent, url: string) => {
        event.preventDefault();
        viewing = url;
    };
</script>

<div class="container-fluid py-4">
    <ListHeader title={t("Photo Gallery")} icon="fa-solid fa-images" />

    {#if photos.data.length === 0}
        <p class="text-muted">{t("No event photos have been uploaded yet.")}</p>
    {:else}
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
            {#each photos.data as photo (photo.id)}
                <div class="col">
                    <div class="card h-100">
                        <div class="ratio ratio-1x1">
                            <a href={photo.large_url} class="d-block w-100 h-100" onclick={(e) => show(e, photo.large_url)}>
                                <img src={photo.small_url} alt={t("Event photo")} class="photo" />
                            </a>
                        </div>
                        <div class="card-body p-2">
                            <div class="small fw-semibold text-truncate">
                                <a href={photo.event_url} class="text-decoration-none">{photo.event_name}</a>
                            </div>
                            {#if photo.event_date}
                                <div class="small text-muted">{photo.event_date}</div>
                            {/if}
                            <div class="small text-muted text-truncate">{t("By :name", { name: photo.uploaded_by })}</div>
                            {#if photo.tagged}
                                <div class="small text-muted text-truncate mt-1">
                                    <i class="fa fa-tag me-1"></i>{photo.tagged}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
            {/each}
        </div>

        <div class="mt-4">
            <Pagination links={photos.meta.links} />
        </div>
    {/if}
</div>

<PhotoModal bind:url={viewing} alt={t("Event photo")} />

<style>
    .photo {
        object-fit: cover;
        width: 100%;
        height: 100%;
    }
</style>
