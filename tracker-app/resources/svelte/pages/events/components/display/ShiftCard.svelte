<!--
    One shift: how full it is, then its roster in sign-up order (on limited shifts that order
    decides who goes and who moves up from stand-by), with Sign up at the end, where the new row
    will land, then guests and cancelled sign-ups, and the shift's other links. A long roster
    also has Sign Up above it, which goes down to that one and gets it ready.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { DisplayShift, EventDisplayViewModel } from "../../models";
    import GuestRow from "./GuestRow.svelte";
    import RosterRow from "./RosterRow.svelte";
    import SameDayNote from "./SameDayNote.svelte";
    import ShiftHeader from "./ShiftHeader.svelte";
    import ShiftLinks from "./ShiftLinks.svelte";
    import ShiftSignUp from "./ShiftSignUp.svelte";

    let { vm, shift }: { vm: EventDisplayViewModel; shift: DisplayShift } = $props();

    const is_open = $derived(vm.isOpen(shift.id));
    const body_id = $derived(`shift-card-${shift.id}`);
</script>

<div id="shift-container-{shift.id}">
    <span id="shift-{shift.id}"></span>
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" role="button" tabindex="0"
             aria-expanded={is_open} aria-controls={body_id}
             onclick={() => vm.toggleShift(shift.id)}
             onkeydown={(e) => {
                 if (e.key === "Enter" || e.key === " ") {
                     e.preventDefault();
                     vm.toggleShift(shift.id);
                 }
             }}>
            <span>{shift.label}</span>
            <i class={["fa-solid collapse-icon", is_open ? "fa-minus" : "fa-plus"]}></i>
        </div>
        {#if is_open}
            <div id={body_id} class="card-body">
                {#if vm.busy_shift === shift.id}
                    <div class="container-fluid p-0 m-0 mb-1" role="progressbar" aria-label={t("Saving...")}>
                        <div class="progress rounded-0">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                        </div>
                    </div>
                {/if}
                <ShiftHeader {shift} />
                {#if shift.same_day}
                    <SameDayNote note={shift.same_day} />
                {/if}

                <div class="roster">
                    {#if shift.sign_up && "url" in shift.sign_up && vm.hasLongRoster(shift)}
                        <div class="shift-signup-jump d-flex justify-content-end mb-2">
                            <button type="button" class="btn btn-sm btn-success" disabled={vm.sending}
                                    onclick={() => vm.requestSignUp(shift.id)}>
                                <i class="fa-solid fa-arrow-down me-1" aria-hidden="true"></i>
                                {shift.sign_up.is_full ? t("Join stand-by") : t("Sign Up")}
                            </button>
                        </div>
                    {/if}

                    {#each shift.going as row (row.id)}
                        <RosterRow {vm} {row} shift_id={shift.id} is_open={shift.is_open} />
                    {/each}

                    {#if shift.stand_by.length > 0}
                        <div class="roster__heading">{t("Stand-by, in sign-up order")}</div>
                        {#each shift.stand_by as row (row.id)}
                            <RosterRow {vm} {row} shift_id={shift.id} is_open={shift.is_open} />
                        {/each}
                    {/if}

                    <div class="shift-signup-actions">
                        {#if shift.sign_up && "url" in shift.sign_up}
                            <ShiftSignUp {vm} sign_up={shift.sign_up} shift_id={shift.id} />
                        {:else if shift.sign_up?.needs_mission_brief}
                            <span class="d-block small text-warning mb-2">
                                {t("Review and acknowledge the mission brief above to enable sign-ups.")}
                            </span>
                        {/if}
                    </div>

                    {#if shift.guests.length > 0}
                        <div class="roster__heading">{t("Guests")}</div>
                        {#each shift.guests as guest (guest.id)}
                            <GuestRow {vm} {guest} shift_id={shift.id} />
                        {/each}
                    {/if}

                    {#if shift.cancelled.length > 0}
                        <div class="roster__heading">{t("Cancelled")}</div>
                        {#each shift.cancelled as row (row.id)}
                            <RosterRow {vm} {row} shift_id={shift.id} is_open={shift.is_open} />
                        {/each}
                    {/if}
                </div>

                <ShiftLinks {vm} {shift} />
            </div>
        {/if}
    </div>
</div>
