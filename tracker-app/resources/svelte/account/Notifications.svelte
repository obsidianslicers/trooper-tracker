<script lang="ts">
    import InputSelect from '$lib/components/form/InputSelect.svelte';
    import SlimView from '$lib/components/ui/SlimView.svelte';
    import {
        type NotificationSettings,
        NotificationsViewModel,
    } from '$lib/domains/account';
    import configStateSvelte from '$lib/states/config-state.svelte';
    import { onMount } from 'svelte';

    interface Props {
        notifications: NotificationSettings;
    }
    let { notifications }: Props = $props();

    let vm = new NotificationsViewModel();

    onMount(() => {
        vm.load(notifications);
    });
</script>

<SlimView>
    <InputSelect
        label="Notification Frequency"
        bind:value={vm.notifications.notificationFrequency}
        options={configStateSvelte.getEnumOptions('notificationFrequency')}
        errors={vm.errors.notificationFrequency}
    />
</SlimView>
