<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";

    interface Props {
        organization_id: number;
        is_club: boolean;
        active: "update" | "costumes";
    }

    let { organization_id, is_club, active }: Props = $props();

    const tabs = $derived([
        {
            key: "update",
            label: t("Update"),
            href: getRoute("admin.organizations.update", { organization: organization_id }),
        },
        ...(is_club
            ? [{
                key: "costumes",
                label: t("Costumes"),
                href: getRoute("admin.organizations.costumes", { organization: organization_id }),
            }]
            : []),
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
