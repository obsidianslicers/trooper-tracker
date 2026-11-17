<script lang="ts">
    import { t } from "$lib/i18n";
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
    {t("Control which channels you receive notifications on. Website notifications appear in your notification inbox.")}
</p>

<NotificationPreferences {vm} />
