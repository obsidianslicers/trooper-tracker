<script lang="ts">
    import { t } from "$lib/i18n";
    import { untrack } from "svelte";
    import AccordionPanel from "$lib/components/AccordionPanel.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import type { NotificationsPageData } from "../models";
    import { NotificationsViewModel } from "../models";
    import AdministrativeNotifications from "./AdministrativeNotifications.svelte";
    import EventNotifications from "./EventNotifications.svelte";
    import TrooperNotifications from "./TrooperNotifications.svelte";

    interface Props {
        notifications: NotificationsPageData;
    }
    let { notifications }: Props = $props();

    let vm = new NotificationsViewModel(untrack(() => notifications));
</script>

<SlimView>
    <AccordionPanel label={t("Organization Event Notifications")}>
        <EventNotifications notifications={vm.page_data} />
    </AccordionPanel>
    <AccordionPanel label={t("Notification Preferences")}>
        <TrooperNotifications notifications={vm.page_data} />
    </AccordionPanel>
    {#if vm.page_data.administrative_notification_enums?.length > 0}
        <AccordionPanel label={t("Administrative Preferences")}>
            <AdministrativeNotifications notifications={vm.page_data} />
        </AccordionPanel>
    {/if}
</SlimView>
