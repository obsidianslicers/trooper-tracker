<script lang="ts">
    import AccordionPanel from "$lib/components/AccordionPanel.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import type { NotificationsPageData } from "$lib/domains/account";
    import { NotificationsViewModel } from "$lib/domains/account";
    import EventNotifications from "./EventNotifications.svelte";

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
        <p>
            Control which channels you receive notifications on. Website
            notifications appear in your notification inbox.
        </p>
    </AccordionPanel>
    {#if vm.page_data.is_administrator}
        <AccordionPanel label="Administrative Preferences">
            <p>
                Control which channels you receive administrative notifications
                on.
            </p>
        </AccordionPanel>
    {/if}
</SlimView>
