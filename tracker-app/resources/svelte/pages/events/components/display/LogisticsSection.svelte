<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventDetails } from "../../models";
    import SectionTitle from "./SectionTitle.svelte";
    import YesNoIcon from "./YesNoIcon.svelte";

    let { logistics }: { logistics: EventDetails["logistics"] } = $props();

    const items = $derived([
        { value: logistics.secure_staging_area, label: t("Secure Staging") },
        { value: logistics.allow_blasters, label: t("Blasters Allowed") },
        { value: logistics.allow_props, label: t("Props Allowed") },
        { value: logistics.parking_available, label: t("Parking Available") },
        { value: logistics.accessible, label: t("Accessible") },
    ]);
</script>

<SectionTitle>{t("Logistics & Support")}</SectionTitle>
<ul class="list-group list-group-flush">
    {#each items as item (item.label)}
        <li class="list-group-item bg-transparent border-bottom">
            <YesNoIcon value={item.value} />
            {item.label}
        </li>
    {/each}
</ul>
{#if logistics.notes_html}
    <div class="small text-muted mt-2 p-1">
        <b>{t("Logistical Notes:")}</b> <i>{@html logistics.notes_html}</i>
    </div>
{/if}
