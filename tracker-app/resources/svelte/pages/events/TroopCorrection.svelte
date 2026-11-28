<!--
    One request for troop credit, opened from the event coordinator's email: who asked, for
    which troop, what they said, and Confirm / Deny while it waits for this coordinator.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import { TroopCorrectionViewModel } from "./models";
    import type { TroopCorrectionPageData } from "./models";

    const page = usePage<TroopCorrectionPageData>();

    pageState.title = t("Troop Credit Request");

    const correction = $derived(page.props.correction);
    const vm = new TroopCorrectionViewModel(page.props.correction.id);

    const status_text: Record<TroopCorrectionPageData["correction"]["status"], string> = {
        awaiting_coordinator: t("Waiting for the event coordinator."),
        awaiting_moderator: t("Waiting for a moderator."),
        approved: t("Approved. The troop counts."),
        denied: t("Denied."),
    };
</script>

<SlimView wide={true}>
    <p>
        {t(":trooper says they trooped this event but never signed up in the tracker.", { trooper: correction.trooper_name })}
    </p>

    <dl class="row mb-3">
        <dt class="col-sm-3">{t("Event")}</dt>
        <dd class="col-sm-9">
            <a href={getRoute("events.display", { event: correction.event.id })}>{correction.event.name}</a>
            {#if correction.shift}
                <span class="d-block small text-muted">{correction.shift}</span>
            {/if}
        </dd>

        <dt class="col-sm-3">{t("Costume")}</dt>
        <dd class="col-sm-9">{correction.costume ?? t("No costume picked")}</dd>

        {#if correction.clubs.length > 0}
            <dt class="col-sm-3">{t("Clubs")}</dt>
            <dd class="col-sm-9">{correction.clubs.join(", ")}</dd>
        {/if}

        <dt class="col-sm-3">{t("Their details")}</dt>
        <dd class="col-sm-9 correction-details">{correction.details}</dd>

        {#if correction.proof_url}
            <dt class="col-sm-3">{t("Proof link")}</dt>
            <dd class="col-sm-9 text-break">
                <a href={correction.proof_url} target="_blank" rel="noopener noreferrer">{correction.proof_url}</a>
            </dd>
        {/if}
    </dl>

    {#if correction.can_answer}
        <p class="fw-semibold">{t("Were they at your troop?")}</p>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-success" disabled={vm.submitting !== null} onclick={vm.confirm}>
                {t("Yes, confirm")}
            </button>
            <button type="button" class="btn btn-outline-danger" disabled={vm.submitting !== null} onclick={vm.deny}>
                {t("No, deny")}
            </button>
        </div>
        <p class="small text-muted mt-2">{t("Deny sends it to a moderator, who makes the final call.")}</p>
    {:else}
        <x-message type="info" icon="fa-solid fa-circle-info">{status_text[correction.status]}</x-message>
    {/if}
</SlimView>

<style>
    .correction-details {
        white-space: pre-line;
    }
</style>
