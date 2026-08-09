<script lang="ts">
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import type { EventNotificationsPageData } from "$lib/domains/account";
    import { EventNotificationsViewModel } from "$lib/domains/account";

    interface Props {
        notifications: EventNotificationsPageData;
    }
    let { notifications }: Props = $props();

    let vm = new EventNotificationsViewModel(notifications);
</script>

<InputContainer>
    <InputSelect
        label="Notification Frequency"
        bind:value={vm.notification_frequency}
        options={vm.notification_frequency_enums}
        change={() => vm.updateNotificationFrequency()}
    />
    <InputHelp>
        How often you want to receive notifications about events added to the
        Trooper Tracker system.
    </InputHelp>
</InputContainer>
<InputContainer>
    <InputSelect
        label="Allow Push Notifications"
        bind:value={vm.push_notifications_enabled}
        options={vm.push_notification_options}
        change={() => vm.updatePushNotifications()}
    />
    <InputHelp>
        If you have installed the tracker app on your mobile device, do you wish
        to receive push alerts? This does not affect emails.
    </InputHelp>
</InputContainer>
<InputHelp>
    Below, you can select the organization/club, region/garrison, and unit/squad
    you want to receive notifications for when events are created.
</InputHelp>
{#each vm.organization_notifications as organization_notification}
    <InputContainer>
        <InputCheckbox
            bind:checked={organization_notification.selected}
            label={organization_notification.name}
            change={() =>
                vm.cascadeOrganizationNotification(organization_notification)}
        />
        {#each organization_notification.regions as region_notification}
            <InputContainer>
                <div class="ms-2 ps-4 border-start border-2">
                    <InputCheckbox
                        bind:checked={region_notification.selected}
                        label={region_notification.name}
                        change={() =>
                            vm.cascadeRegionNotification(region_notification)}
                    />
                    {#each region_notification.units as unit_notification}
                        <InputContainer>
                            <div class="ms-4 ps-4">
                                <InputCheckbox
                                    bind:checked={unit_notification.selected}
                                    label={unit_notification.name}
                                />
                            </div>
                        </InputContainer>
                    {/each}
                </div>
            </InputContainer>
        {/each}
    </InputContainer>
{/each}
