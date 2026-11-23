<!--
    A roster row's status, with the buttons worth having right on the row: Approve / Reject for
    moderators of manual-selection events, Sign up again once cancelled, and Made It / Missed It
    after the troop.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventDisplayViewModel, TrooperRow } from "../../models";
    import BusySpinner from "./BusySpinner.svelte";
    import SignUpStatus from "./SignUpStatus.svelte";

    interface Props {
        vm: EventDisplayViewModel;
        row: TrooperRow;
        shift_id: number;
        is_open: boolean;
    }

    let { vm, row, shift_id, is_open }: Props = $props();

    const actions = $derived(row.actions);
    const pending_hint = t("Select a costume, or mark attending without one, to confirm your spot.");

    const again_key = $derived(`sign-up-again-${row.id}`);
    const answer_key = $derived(`attendance-${row.id}`);

    //  pressing the answer already recorded changes nothing, so it doesn't send
    const answer = (status: "attended" | "unabletoattend") => {
        if (actions.attendance && actions.attendance.answer !== status) {
            vm.send(actions.attendance.url, { status }, { shift_id, busy: answer_key });
        }
    };

    const send = (data: Record<string, unknown>, busy?: string) =>
        vm.send(actions.update_url, data, { shift_id, busy });
</script>

{#if actions.approve || actions.reject}
    <div class="d-flex gap-1 justify-content-end">
        {#if actions.approve}
            <button type="button" class="btn btn-sm btn-outline-success" disabled={vm.sending}
                    onclick={() => send({ status: "going" })}>{t("Approve")}</button>
        {/if}
        {#if actions.reject}
            <button type="button" class="btn btn-sm btn-outline-danger" disabled={vm.sending}
                    onclick={() => send({ status: "standby" })}>{t("Reject")}</button>
        {/if}
    </div>
{:else if actions.needs_costume_to_approve}
    <SignUpStatus status={actions.needs_costume_to_approve} block />
    <i class="small text-muted">{pending_hint}</i>
{:else if actions.sign_up_again}
    <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action" disabled={vm.sending}
            aria-busy={vm.isBusy(again_key)}
            onclick={() => send(actions.sign_up_again === "resign_up" ? { resign_up: 1 } : { status: "going" }, again_key)}>
        <BusySpinner busy={vm.isBusy(again_key)} />
        {t("Sign up again")}
    </button>
{:else if actions.attendance}
    {@const chosen = actions.attendance.answer}
    <!-- stacked until lg: below it the roster's status column is narrow -->
    <div class="d-inline-flex flex-column flex-lg-row gap-1" role="group" aria-label={t("Did you make it?")}
         aria-busy={vm.isBusy(answer_key)}>
        <button type="button" class="btn btn-sm text-nowrap {chosen === 'attended' ? 'btn-success' : 'btn-outline-success'}"
                aria-pressed={chosen === "attended"} disabled={vm.sending}
                onclick={() => answer("attended")}>{t("Made It")}</button>
        <button type="button" class="btn btn-sm text-nowrap {chosen === 'unabletoattend' ? 'btn-danger' : 'btn-outline-danger'}"
                aria-pressed={chosen === "unabletoattend"} disabled={vm.sending}
                onclick={() => answer("unabletoattend")}>{t("Missed It")}</button>
    </div>
{:else if !(is_open && row.status.value === "cancelled")}
    <!-- a cancelled row sits under "Cancelled", so it needs no status word -->
    <SignUpStatus status={row.status} />
    {#if row.status.value === "pending"}
        <div class="small text-muted">{pending_hint}</div>
    {/if}
{/if}

{#if row.approved_by}
    <div class="small text-muted fst-italic">{row.approved_by}</div>
{/if}
