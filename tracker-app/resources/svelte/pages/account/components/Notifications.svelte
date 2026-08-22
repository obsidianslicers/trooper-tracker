<script lang="ts">
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

    let vm = new NotificationsViewModel(notifications);
</script>

<SlimView>
    <AccordionPanel label="Organization Event Notifications">
        <EventNotifications notifications={vm.page_data} />
    </AccordionPanel>
    <AccordionPanel label="Notification Preferences">
        <TrooperNotifications notifications={vm.page_data} />
    </AccordionPanel>
    {#if vm.page_data.is_administrator}
        <AccordionPanel label="Administrative Preferences">
            <AdministrativeNotifications notifications={vm.page_data} />
        </AccordionPanel>
    {/if}
</SlimView>
