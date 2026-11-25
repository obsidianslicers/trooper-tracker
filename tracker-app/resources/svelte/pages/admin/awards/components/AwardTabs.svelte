<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";
    import TabHeader from "$lib/components/ui/tabs/TabHeader.svelte";

    interface Props {
        award_id: number;
        active: "overview" | "troopers";
    }

    let { award_id, active }: Props = $props();

    const tabs = $derived([
        { key: "overview", label: t("Overview"), href: getRoute("admin.awards.edit", { award: award_id }) },
        { key: "troopers", label: t("Troopers Awarded"), href: getRoute("admin.awards.troopers.index", { award: award_id }) },
    ]);
</script>

<TabHeader>
    {#each tabs as tab (tab.key)}
        <li class="nav-item">
            <a
                class={["nav-link", active === tab.key && "active"]}
                href={tab.href}
                aria-current={active === tab.key ? "page" : undefined}
            >
                {tab.label}
            </a>
        </li>
    {/each}
</TabHeader>
