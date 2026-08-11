<script lang="ts">
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import type { NotificationPreferenceViewModel } from "$lib/domains/account/vms/NotificationPreferenceViewModel.svelte";

    interface Props {
        vm: NotificationPreferenceViewModel;
    }
    let { vm }: Props = $props();
</script>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Notification Type</th>
            <th class="text-center">Email</th>
            <th class="text-center">Push</th>
            <th class="text-center">Website</th>
        </tr>
    </thead>
    <tbody>
        {#each vm.notification_enums as notification}
            <tr>
                <td>{notification.label}</td>
                <td class="text-center">
                    <InputCheckbox
                        bind:checked={
                            vm.notification_preferences[
                                notification.value as string
                            ].mail
                        }
                        change={() =>
                            vm.updateMailNotificationPreference(
                                notification.value as string,
                            )}
                    />
                </td>
                <td class="text-center">
                    <InputCheckbox
                        bind:checked={
                            vm.notification_preferences[
                                notification.value as string
                            ].fcm
                        }
                        change={() =>
                            vm.updateFcmNotificationPreference(
                                notification.value as string,
                            )}
                    />
                </td>
                <td class="text-center">
                    <InputCheckbox
                        bind:checked={
                            vm.notification_preferences[
                                notification.value as string
                            ].database
                        }
                        change={() =>
                            vm.updateDatabaseNotificationPreference(
                                notification.value as string,
                            )}
                    />
                </td>
            </tr>
        {/each}
    </tbody>
</table>
