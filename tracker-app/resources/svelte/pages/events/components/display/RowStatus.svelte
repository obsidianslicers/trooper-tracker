<!--
    A roster row's status, with the buttons worth having right on the row: Approve / Reject for
    moderators of manual-selection events, Sign up again once cancelled, and Made It / Missed It
    after the troop.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventDisplayViewModel, TrooperRow } from "../../models";
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

    const send = (data: Record<string, unknown>) => vm.send(actions.update_url, data, { shift_id });
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
            onclick={() => send(actions.sign_up_again === "resign_up" ? { resign_up: 1 } : { status: "going" })}>
        {t("Sign up again")}
    </button>
{:else if actions.attendance}
    <div class="input-group input-group-sm d-inline-flex w-auto">
        <a class="btn btn-outline-success" href={actions.attendance.made_it_url}>{t("Made It")}</a>
        <a class="btn btn-outline-danger" href={actions.attendance.missed_it_url}>{t("Missed It")}</a>
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
