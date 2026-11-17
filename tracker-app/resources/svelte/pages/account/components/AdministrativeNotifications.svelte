<script lang="ts">
    import { t } from "$lib/i18n";
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

<p>{t("Control which channels you receive administrative notifications on.")}</p>

<NotificationPreferences {vm} />
