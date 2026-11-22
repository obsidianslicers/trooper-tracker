<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventDetails } from "../../models";

    let { event }: { event: EventDetails } = $props();
</script>

{#snippet adminButtons(classes: string)}
    {#if event.edit_url || event.copy_url}
        <div class={["btn-group mb-2", classes]}>
            {#if event.edit_url}
                <a href={event.edit_url} class="btn border-white">
                    <i class="fa fa-fw fa-edit"></i>
                    {t("Edit")}
                </a>
            {/if}
            {#if event.copy_url}
                <a href={event.copy_url} class="btn border-white">
                    <i class="fa fa-fw fa-copy"></i>
                    {t("Copy")}
                </a>
            {/if}
        </div>
    {/if}
{/snippet}

<div class={["card-header d-flex align-items-center", `bg-${event.header.color}`]}>
    <span class="p-2">
        {#if event.header.logo_url}
            <img src={event.header.logo_url} width="32" height="32" alt="" />
        {:else}
            <i
                class="fa fa-fw fa-circle-info fa-2x text-white not-counted-logo"
                title={event.not_counted_notice ?? ""}
                aria-hidden="true"
            ></i>
        {/if}
    </span>
    <span class="p-2">
        <h4 class="text-white">{event.name}</h4>
    </span>
    {@render adminButtons("ms-auto d-none d-md-flex")}
</div>

{#if event.edit_url || event.copy_url}
    <div class="row d-md-none mx-0 mt-3">
        <div class="col-12">
            {@render adminButtons("w-100")}
        </div>
    </div>
{/if}
