<script lang="ts">
    import { untrack } from "svelte";
    import type { AdministrativeNotificationsPageData } from "../models";
    import { AdministrativeNotificationsViewModel } from "../models";
    import NotificationPreferences from "./NotificationPreferences.svelte";

    interface Props {
        notifications: AdministrativeNotificationsPageData;
    }
    let { notifications }: Props = $props();

    let vm = new AdministrativeNotificationsViewModel(untrack(() => notifications));
</script>

<p>Control which channels you receive administrative notifications on.</p>

<NotificationPreferences {vm} />
