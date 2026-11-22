<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventDetails } from "../../models";
    import SectionTitle from "./SectionTitle.svelte";

    let { contact, coordinator }: Pick<EventDetails, "contact" | "coordinator"> = $props();
</script>

<SectionTitle>{t("Point of Contact")}</SectionTitle>
<div class="mt-3 ps-3">
    {#if contact}
        <p class="mb-1"><i class="fa fa-fw fa-user me-2"></i>{contact.name}</p>
        {#if contact.phone}
            <p class="mb-1">
                <a href="tel:{contact.phone}"><i class="fa fa-fw fa-phone me-2"></i>{contact.phone}</a>
            </p>
        {/if}
        {#if contact.email}
            <p class="mb-1">
                <a href="mailto:{contact.email}"><i class="fa fa-fw fa-envelope me-2"></i>{contact.email}</a>
            </p>
        {/if}
    {:else}
        <p class="mb-1 text-muted">{t("Unavailable")}</p>
    {/if}
</div>

{#if coordinator}
    <div class="mt-3">
        <SectionTitle>{t("Event Coordinator")}</SectionTitle>
        <div class="mt-3 ps-3">
            <p class="mb-1">
                <a href={coordinator.url}><i class="fa fa-fw fa-user-shield me-2"></i>{coordinator.name}</a>
            </p>
        </div>
    </div>
{/if}
