<script lang="ts">
    import type { AdministrativeNotificationsPageData } from "$lib/domains/account";
    import { AdministrativeNotificationsViewModel } from "$lib/domains/account";
    import NotificationPreferences from "./NotificationPreferences.svelte";

    interface Props {
        notifications: AdministrativeNotificationsPageData;
    }
    let { notifications }: Props = $props();

    let vm = new AdministrativeNotificationsViewModel(notifications);
</script>

<p>Control which channels you receive administrative notifications on.</p>

<NotificationPreferences {vm} />
