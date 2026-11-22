<!--
    The shift's quieter actions under the roster: Add a friend and Add a guest (once you're going,
    or any time for moderators), Add to calendar once you're signed up, and the shift's CSV for
    moderators. The people added land in the roster with their own Edit.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import TrooperPicker from "$lib/components/TrooperPicker.svelte";
    import { tick, untrack } from "svelte";
    import { AddFriendViewModel, type DisplayShift, type EventDisplayViewModel } from "../../models";
    import SignUpChoices from "./SignUpChoices.svelte";

    let { vm, shift }: { vm: EventDisplayViewModel; shift: DisplayShift } = $props();

    const links = $derived(shift.links);
    const friend = new AddFriendViewModel(untrack(() => shift.links.friend_form_url));

    let adding_guest = $state(false);
    let guest_name = $state("");
    let guest_input: HTMLInputElement | undefined = $state();

    const toggleGuest = async () => {
        adding_guest = !adding_guest;
        await tick();
        guest_input?.focus();
    };

    const addGuest = () => vm.send(links.guest_url, { guest_names: guest_name }, {
        shift_id: shift.id,
        onSuccess: () => {
            adding_guest = false;
            guest_name = "";
        },
    });

    const addFriend = () => vm.send(links.signup_url, friend.payload(), {
        shift_id: shift.id,
        onSuccess: friend.reset,
    });
</script>

{#if links.can_add_friend || links.can_add_guest || links.calendar || links.csv_url}
    <div class="shift-links">
        <div class="shift-links__row">
            {#if links.can_add_friend && friend.form === null}
                <div class="shift-links__friend">
                    <TrooperPicker as_button mode="friends" button_label={t("Add a friend")}
                                   modal_title={t("Find a Trooper")} onSelected={friend.picked} />
                </div>
            {/if}
            {#if links.can_add_guest}
                <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action" onclick={toggleGuest}>
                    {t("Add a guest")}
                </button>
            {/if}
            {#if links.calendar}
                <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action dropdown-toggle"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        {t("Add to calendar")}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" target="_blank" rel="noopener noreferrer" href={links.calendar.google}>{t("Google Calendar")}</a></li>
                        <li><a class="dropdown-item" target="_blank" rel="noopener noreferrer" href={links.calendar.outlook}>{t("Outlook")}</a></li>
                        <li><a class="dropdown-item" target="_blank" rel="noopener noreferrer" href={links.calendar.yahoo}>{t("Yahoo Calendar")}</a></li>
                        <li><a class="dropdown-item" href={links.calendar.ics}>{t("Apple Calendar or other (.ics file)")}</a></li>
                    </ul>
                </div>
            {/if}
            {#if links.csv_url}
                <a class="btn btn-link p-0 text-muted d-none d-md-inline shift-links__csv" href={links.csv_url}>
                    {t("Download this shift as CSV")}
                </a>
            {/if}
        </div>

        {#if friend.is_loading}
            <p class="small text-muted mb-0"><i class="fa fa-fw fa-spinner fa-spin"></i></p>
        {/if}
        {#if friend.error}
            <p class="small text-danger mb-0">{friend.error}</p>
        {/if}

        {#if friend.form && friend.friend}
            <!-- once a trooper is picked: their choices and Confirm -->
            <div class="friend-signup-step">
                <div class="shift-signup-primary shift-signup-primary--form">
                    <span class="shift-signup-primary__label">{t("Sign up :name", { name: friend.friend.trooper.name })}</span>
                    <SignUpChoices form={friend.form} key="friend-{shift.id}" />
                    <div class="shift-signup__confirm">
                        <button type="button" class="btn btn-sm btn-success"
                                disabled={!friend.form.can_confirm || vm.sending} onclick={addFriend}>
                            {t("Confirm")}
                        </button>
                        <button type="button" class="btn btn-link btn-sm text-muted" onclick={friend.reset}>
                            {t("Never mind")}
                        </button>
                    </div>
                    {#if friend.friend.requires_costume && friend.form.costume_id === ""}
                        <span class="small text-muted shift-signup__hint">{t("Pick their costume, then press Confirm.")}</span>
                    {/if}
                </div>
            </div>
        {/if}

        {#if links.can_add_guest && adding_guest}
            <!-- a guest has no tracker account: just their name -->
            <div class="shift-signup-primary shift-signup-primary--form">
                <span class="shift-signup-primary__label">{t("Add a guest without a tracker account")}</span>
                <input type="text" class="form-control form-control-sm" maxlength="255"
                       placeholder={t("Guest's name")} aria-label={t("Guest's name")}
                       bind:this={guest_input} bind:value={guest_name} />
                <div class="shift-signup__confirm">
                    <button type="button" class="btn btn-sm btn-success"
                            disabled={guest_name.trim() === "" || vm.sending} onclick={addGuest}>
                        {t("Confirm")}
                    </button>
                    <button type="button" class="btn btn-link btn-sm text-muted"
                            onclick={() => { adding_guest = false; guest_name = ""; }}>
                        {t("Never mind")}
                    </button>
                </div>
            </div>
        {/if}
    </div>
{/if}
