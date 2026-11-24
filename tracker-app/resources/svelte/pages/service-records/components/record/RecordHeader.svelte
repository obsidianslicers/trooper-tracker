<!--
    The trooper's name and rank, plugin badges, and the actions: plugin buttons, Fix Missing
    Credit and Edit for those who may.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import PluginSlot from "$lib/components/PluginSlot.svelte";
    import type { RecordTrooper } from "../../models/record-types";

    let { trooper, slots }: { trooper: RecordTrooper; slots: Record<string, string> } = $props();

    const actions = $derived(slots["service-records.trooper.actions"] ?? "");
    const hasActions = $derived(actions.trim() !== "" || trooper.edit_url !== null);
</script>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h2 class="h1 mb-0 text-upper">
            {trooper.display_name}
            <span class="text-{trooper.rank_theme} fw-bold text-uppercase small">// {trooper.rank_title}</span>
        </h2>
        <PluginSlot name="service-records.trooper.badges" html={slots["service-records.trooper.badges"] ?? ""} />
    </div>
    {#if hasActions}
        <div class="btn-group ms-auto flex-shrink-0">
            {#if actions}
                {@html actions}
            {/if}
            {#if trooper.missing_credit_url}
                <a href={trooper.missing_credit_url} class="btn btn-outline-danger">
                    <i class="fa fa-fw fa-triangle-exclamation"></i>
                    {t("Fix Missing Credit")}
                </a>
            {/if}
            {#if trooper.edit_url}
                <a href={trooper.edit_url} class="btn btn-outline-warning">
                    <i class="fa fa-fw fa-edit"></i>
                    {t("Edit")}
                </a>
            {/if}
        </div>
    {/if}
</div>
