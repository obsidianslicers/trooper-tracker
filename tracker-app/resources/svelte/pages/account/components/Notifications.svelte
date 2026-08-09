<script lang="ts">
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
    <InputContainer>
        <InputSelect
            label="Notification Frequency"
            bind:value={vm.form.notification_frequency}
            options={vm.notification_frequency_enums}
            errors={vm.errors.notification_frequency}
            change={() => vm.updateNotificationFrequency()}
        />
        <InputHelp>
            How often you want to receive notifications about events added to
            the Trooper Tracker system.
        </InputHelp>
    </InputContainer>
</SlimView>
