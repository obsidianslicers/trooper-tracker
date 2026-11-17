<script lang="ts">
    import { t } from "$lib/i18n";
    import PluginSlot from "$lib/components/PluginSlot.svelte";
    import Alert from "$lib/components/ui/Alert.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";

    pageState.title = t("Pending Membership");
</script>

<SlimView>
    <Alert type="warning">
        <strong>{t("Your application is under review.")}</strong>
        <div class="mt-2">
            {t("A moderator will review your submission and you will receive an email when a decision has been made.")}
        </div>
    </Alert>
    <PluginSlot name="account.pending.sections" />
</SlimView>
