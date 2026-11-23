<script lang="ts">
    import PluginSlot from "$lib/components/PluginSlot.svelte";
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import { fade } from "svelte/transition";
    import DashboardCard from "./components/DashboardCard.svelte";
    import type { CommandStaffDashboardPageData } from "./models/dashboard";
    import { DashboardViewModel } from "./models/vms/DashboardViewModel.svelte";

    const page = usePage<CommandStaffDashboardPageData>();

    pageState.title = t("Command Staff");

    const vm = new DashboardViewModel(
        page.props.counts,
        page.props.system_check_problems,
        page.props.trooper_id,
        getRoute("admin.troopers.permissions.approvals.index"),
    );

    // a plugin's card is the Blade <x-dashboard-card>, which opens its data-route when clicked
    function openPluginCard(event: MouseEvent) {
        const card = (event.target as HTMLElement).closest<HTMLElement>("[data-route]");

        if (card?.dataset.route && !(event.target as HTMLElement).closest("a, button")) {
            window.location.href = card.dataset.route;
        }
    }
</script>

{#each vm.alerts as alert (alert.key)}
    <div class="alert alert-warning alert-dismissible mt-2" transition:fade>
        <strong>
            <i class="fa fa-fw fa-solid fa-circle-exclamation"></i>
            {#if alert.href}
                <a href={alert.href} class="alert-link">{alert.text}</a>
            {:else}
                {alert.text}
            {/if}
        </strong>
        <button
            type="button"
            class="btn-close"
            aria-label={t("Close")}
            onclick={() => vm.dismiss(alert)}
        ></button>
    </div>
{/each}

<!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_static_element_interactions -->
<div class="row row-cols-1 row-cols-md-3 g-4 mt-1 dashboard-cards" onclick={openPluginCard}>
    {#each page.props.cards as card (card.key)}
        <DashboardCard {...vm.card(card.key)} href={card.url} />
    {/each}
    <PluginSlot name="admin.dashboard.cards" html={page.props.plugin_cards} />
</div>

<style>
    .dashboard-cards :global(.plugin-slot) {
        display: contents;
    }
</style>
