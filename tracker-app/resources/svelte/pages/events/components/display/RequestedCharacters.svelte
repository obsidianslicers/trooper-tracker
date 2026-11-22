<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventDetails } from "../../models";
    import SectionTitle from "./SectionTitle.svelte";

    let { requested }: { requested: EventDetails["requested_characters"] } = $props();
</script>

<SectionTitle>{t("Requested Characters")}</SectionTitle>
<p>
    {t("Number of Characters:")}
    {#if requested.number}
        {requested.number}
    {:else}
        <span class="text-muted">{t("not specified")}</span>
    {/if}
</p>
<p>
    {t("Character Types:")}
    {#if requested.types}
        {requested.types}
    {:else}
        <span class="text-muted">{t("not specified")}</span>
    {/if}
</p>
