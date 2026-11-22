<!--
    One guest (no tracker account) on a shift's roster, laid out like a trooper's row. Whoever
    added them gets Edit, to cancel them; once cancelled, Sign up again. Moderators of
    manual-selection events get Approve / Reject.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { guestRowKey, type EventDisplayViewModel, type GuestRow } from "../../models";
    import SignUpStatus from "./SignUpStatus.svelte";

    let { vm, guest, shift_id }: { vm: EventDisplayViewModel; guest: GuestRow; shift_id: number } = $props();

    const key = $derived(guestRowKey(guest.id));
    const editing = $derived(guest.can_cancel && vm.isEditing(key));

    const send = (status: string) => vm.send(guest.update_url, { status }, { shift_id });

    const cancel = () => vm.confirm({
        title: t("Cancel their sign-up"),
        message: guest.cancel_confirm,
        label: t("Cancel their sign-up"),
        danger: true,
        run: () => send("cancelled"),
    });
</script>

<div class={["roster-row", `guest-status-${guest.status.value}`]} id="roster-guest-{guest.id}">
    <div class="roster-row__main">
        <span class="roster-row__number"></span>
        <div class="roster-row__who">
            {guest.name}
            {#if guest.added_by}
                <div class="text-muted small fst-italic">{t("Added by :name", { name: guest.added_by })}</div>
            {/if}
        </div>
        <div class="roster-row__costume"></div>
        <div class="roster-row__status">
            {#if guest.approve || guest.reject}
                <div class="d-flex gap-1 justify-content-end">
                    {#if guest.approve}
                        <button type="button" class="btn btn-sm btn-outline-success" disabled={vm.sending}
                                onclick={() => send("going")}>{t("Approve")}</button>
                    {:else}
                        <button type="button" class="btn btn-sm btn-outline-danger" disabled={vm.sending}
                                onclick={() => send("standby")}>{t("Reject")}</button>
                    {/if}
                </div>
            {:else if guest.sign_up_again}
                <SignUpStatus status={guest.status} block />
                <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action"
                        disabled={vm.sending} onclick={() => send(guest.sign_up_again!)}>
                    {t("Sign up again")}
                </button>
            {:else}
                <SignUpStatus status={guest.status} />
            {/if}
            {#if guest.approved_by}
                <div class="small text-muted fst-italic">{guest.approved_by}</div>
            {/if}
        </div>
        <div class="roster-row__edit">
            {#if guest.can_cancel}
                <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action"
                        aria-controls="roster-guest-editor-{guest.id}" aria-expanded={editing}
                        onclick={() => vm.toggleEdit(key)}>
                    {editing ? t("Done") : t("Edit")}
                </button>
            {/if}
        </div>
    </div>
    {#if editing}
        <div class="roster-row__editor text-md-end" id="roster-guest-editor-{guest.id}">
            <button type="button" class="btn btn-link btn-sm p-0 text-danger" onclick={cancel}>
                {t("Cancel their sign-up")}
            </button>
        </div>
    {/if}
</div>
