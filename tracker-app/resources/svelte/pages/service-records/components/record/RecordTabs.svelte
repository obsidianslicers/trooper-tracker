<!-- The pill tabs over the record's right column. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { RecordTab } from "../../models/vms/ServiceRecordViewModel.svelte";

    interface Props {
        current: RecordTab;
        showUpcoming: boolean;
        showSupport: boolean;
        onselect: (tab: RecordTab) => void;
    }
    let { current, showUpcoming, showSupport, onselect }: Props = $props();

    const tabs = $derived(
        [
            showUpcoming && { id: "upcoming" as RecordTab, label: t("Upcoming") },
            { id: "history" as RecordTab, label: t("History") },
            { id: "awards" as RecordTab, label: t("Awards") },
            { id: "costumes" as RecordTab, label: t("Costumes") },
            { id: "photos" as RecordTab, label: t("Photos") },
            showSupport && { id: "support" as RecordTab, label: t("Support") },
        ].filter((tab) => tab !== false),
    );
</script>

<ul class="nav nav-pills p-3 border-bottom" role="tablist">
    {#each tabs as tab (tab.id)}
        <li class="nav-item" role="presentation">
            <button
                type="button"
                class={["nav-link text-uppercase small fw-bold", { active: current === tab.id }]}
                role="tab"
                aria-selected={current === tab.id}
                tabindex={current === tab.id ? 0 : -1}
                onclick={() => onselect(tab.id)}
            >
                {tab.label}
            </button>
        </li>
    {/each}
</ul>
