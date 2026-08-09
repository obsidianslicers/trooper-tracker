<script lang="ts">
    import AccordionPanel from "$lib/components/AccordionPanel.svelte";
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import type { NotificationsPageData } from "$lib/domains/account";
    import { NotificationsViewModel } from "$lib/domains/account";

    interface Props {
        notifications: NotificationsPageData;
    }
    let { notifications }: Props = $props();

    let vm = new NotificationsViewModel(notifications);
</script>

<SlimView>
    <AccordionPanel label="Event Notifications">
        <InputContainer>
            <InputSelect
                label="Notification Frequency"
                bind:value={vm.form.notification_frequency}
                options={vm.notification_frequency_enums}
                errors={vm.errors.notification_frequency}
                change={() => vm.updateNotificationFrequency()}
            />
            <InputHelp>
                How often you want to receive notifications about events added
                to the Trooper Tracker system.
            </InputHelp>
        </InputContainer>
        {#each vm.form.organization_notifications as organization_notification}
            <InputContainer>
                <InputCheckbox
                    bind:checked={organization_notification.selected}
                    label={organization_notification.name}
                />
                {#each organization_notification.regions as region_notification}
                    <InputContainer>
                        <div class="ms-2 ps-4 border-start border-2">
                            <InputCheckbox
                                bind:checked={region_notification.selected}
                                label={region_notification.name}
                            />
                            {#each region_notification.units as unit_notification}
                                <InputContainer>
                                    <div class="ms-4 ps-4">
                                        <InputCheckbox
                                            bind:checked={
                                                unit_notification.selected
                                            }
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
    </AccordionPanel>
</SlimView>
