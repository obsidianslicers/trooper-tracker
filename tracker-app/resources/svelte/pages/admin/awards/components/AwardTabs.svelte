<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";

    interface Props {
        award_id: number;
        active: "overview" | "troopers";
    }

    let { award_id, active }: Props = $props();

    const tabs = $derived([
        { key: "overview", label: t("Overview"), href: getRoute("admin.awards.update", { award: award_id }) },
        { key: "troopers", label: t("Troopers Awarded"), href: getRoute("admin.awards.list-troopers", { award: award_id }) },
    ]);
</script>

<div class="overflow-auto tabs-scroll-mobile">
    <ul class="nav nav-tabs flex-nowrap flex-md-wrap w-max-content w-md-auto">
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
    </ul>
</div>
