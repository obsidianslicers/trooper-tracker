<script lang="ts">
    import { t } from "$lib/i18n";
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import type { NotificationPreferenceViewModel } from "../models";

    interface Props {
        vm: NotificationPreferenceViewModel;
    }
    let { vm }: Props = $props();
</script>

<table class="table table-striped">
    <thead>
        <tr>
            <th>{t("Notification Type")}</th>
            <th class="text-center">{t("Email")}</th>
            <th class="text-center">{t("Push")}</th>
            <th class="text-center">{t("Website")}</th>
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
                        onchange={() =>
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
                        onchange={() =>
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
                        onchange={() =>
                            vm.updateDatabaseNotificationPreference(
                                notification.value as string,
                            )}
                    />
                </td>
            </tr>
        {/each}
    </tbody>
</table>
