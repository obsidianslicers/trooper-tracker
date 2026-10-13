<script lang="ts">
    import { untrack } from "svelte";
    import type { TrooperNotificationsPageData } from "../models";
    import { TrooperNotificationsViewModel } from "../models";
    import NotificationPreferences from "./NotificationPreferences.svelte";
    interface Props {
        notifications: TrooperNotificationsPageData;
    }
    let { notifications }: Props = $props();

    let vm = new TrooperNotificationsViewModel(untrack(() => notifications));
</script>

<p>
    Control which channels you receive notifications on. Website notifications
    appear in your notification inbox.
</p>

<NotificationPreferences {vm} />
