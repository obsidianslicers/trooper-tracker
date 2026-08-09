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
    <AccordionPanel label="Event Notifications">
        <EventNotifications notifications={vm.page_data} />
    </AccordionPanel>
</SlimView>
