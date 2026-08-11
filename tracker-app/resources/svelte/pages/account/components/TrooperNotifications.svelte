<script lang="ts">
    import type { TrooperNotificationsPageData } from "$lib/domains/account";
    import { TrooperNotificationsViewModel } from "$lib/domains/account";
    import NotificationPreferences from "./NotificationPreferences.svelte";
    interface Props {
        notifications: TrooperNotificationsPageData;
    }
    let { notifications }: Props = $props();

    let vm = new TrooperNotificationsViewModel(notifications);
</script>

<p>
    Control which channels you receive notifications on. Website notifications
    appear in your notification inbox.
</p>

<NotificationPreferences {vm} />
