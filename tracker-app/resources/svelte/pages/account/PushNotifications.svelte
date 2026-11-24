<script lang="ts">
    import { t } from "$lib/i18n";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import TroopsToConfirm from "./components/TroopsToConfirm.svelte";
    import NotificationRow from "./components/NotificationRow.svelte";
    import { PushNotificationsViewModel, type PushNotificationsPageData } from "./models";

    const page = usePage<PushNotificationsPageData>();

    pageState.title = t("Notifications");

    const vm = $derived(new PushNotificationsViewModel(page.props));
</script>

<SlimView>
    {#if vm.page.troops_to_confirm.length > 0}
        <TroopsToConfirm troops={vm.page.troops_to_confirm} onanswer={vm.answer} />
    {/if}

    {#if vm.isEmpty}
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-0">{t("No notifications yet.")}</p>
            </div>
        </div>
    {:else if vm.page.notifications.length > 0}
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action" onclick={vm.clearAll}>
                {t("Clear all")}
            </button>
        </div>

        <div class="list-group">
            {#each vm.page.notifications as notification (notification.id)}
                <NotificationRow {notification} onopen={vm.open} />
            {/each}
        </div>
    {/if}
</SlimView>
