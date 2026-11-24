<!-- The Costumes tab: a handler's note, or their clubs and the costumes they wear. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { TrooperCostume, TrooperOrganization } from "../../models/record-types";
    import OrganizationsList from "./OrganizationsList.svelte";

    interface Props {
        isHandler: boolean;
        organizations: TrooperOrganization[];
        costumes: TrooperCostume[];
    }
    let { isHandler, organizations, costumes }: Props = $props();
</script>

{#if isHandler}
    {t("Handlers don't typically have costumes, but they can still earn awards and serve in missions!")}
{:else}
    <OrganizationsList {organizations} />
    <br />
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        {#each costumes as costume (costume.id)}
            <div class="col">
                <div class="card h-100">
                    <div class="card-header images p-3">
                        {#if costume.image_urls.length > 0}
                            <div class="d-flex gap-2 overflow-auto pb-2 strip">
                                {#each costume.image_urls as url (url)}
                                    <div class="flex-shrink-0">
                                        <img src={url} alt={costume.name} class="rounded border thumb" />
                                    </div>
                                {/each}
                            </div>
                        {:else}
                            <div class="rounded d-flex align-items-center justify-content-center no-image">
                                <i class="fa fa-fw fa-user text-muted fs-4 pb-2"></i>
                            </div>
                        {/if}
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold mb-1">{costume.name}</h5>
                        <p class="card-text small mb-3">{costume.organizations}</p>
                    </div>
                </div>
            </div>
        {:else}
            <div class="col-12">
                <div class="text-center py-5 bg-light rounded border">
                    <p class="mt-2 text-muted">{t("No Attached Costumes ... Yet!")}</p>
                </div>
            </div>
        {/each}
    </div>
{/if}

<style>
    .images {
        height: 100px;
    }

    .thumb {
        width: 80px;
        height: 80px;
        object-fit: cover;
    }

    .no-image {
        height: 80px;
        border: 2px dashed #454545;
    }

    .strip::-webkit-scrollbar {
        height: 4px;
    }

    .strip::-webkit-scrollbar-thumb {
        background: #dee2e6;
        border-radius: 10px;
    }
</style>
