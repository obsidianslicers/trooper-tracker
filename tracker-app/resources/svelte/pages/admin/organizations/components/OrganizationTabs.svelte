<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";
    import TabHeader from "$lib/components/ui/tabs/TabHeader.svelte";

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
            href: getRoute("admin.organizations.edit", { organization: organization_id }),
        },
        ...(is_club
            ? [{
                key: "costumes",
                label: t("Costumes"),
                href: getRoute("admin.organizations.costumes.index", { organization: organization_id }),
            }]
            : []),
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
