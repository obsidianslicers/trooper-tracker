<!--
    "Your sign-up" near the top: where the viewer stands on each shift, with a link to their row
    (opening its Edit), or a Sign up link that jumps to the roster. On phones a bar along the
    bottom says the same, wherever they've scrolled to.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventDisplayViewModel, MySignUps } from "../../models";
    import SignUpStatus from "./SignUpStatus.svelte";

    let { vm, summary }: { vm: EventDisplayViewModel; summary: MySignUps } = $props();

    //  the server sends each sign-up's time only when the event has more than one shift
    const multiple_shifts = $derived(summary.sign_ups.some((s) => s.time !== null));
    const active = $derived(summary.sign_ups.filter((s) => s.is_active));
    const first = $derived(active[0] ?? null);
</script>

{#if summary.sign_ups.length > 0}
    <div class="my-signup-summary card mb-3 d-none d-md-block">
        <div class="card-body py-2">
            <div class="fw-semibold mb-1">{t("Your sign-up")}</div>
            <ul class={["my-signup-summary__list list-unstyled mb-0", multiple_shifts && "my-signup-summary__list--shifts"]}>
                {#each summary.sign_ups as sign_up (sign_up.id)}
                    <li class="my-signup-summary__row">
                        {#if multiple_shifts}
                            <span class="my-signup-summary__time text-muted small">{sign_up.time}</span>
                        {/if}
                        <SignUpStatus status={sign_up.status} classes="my-signup-summary__status" />
                        <span class="my-signup-summary__costume">
                            {#if !sign_up.requires_costume}
                                <!-- an event type troopers don't wear a costume to -->
                                <i class="text-muted">{t("No costume needed for this event")}</i>
                            {:else if sign_up.costume}
                                {sign_up.costume}
                            {:else if sign_up.is_without_costume}
                                {t("Attending without a costume")}
                            {:else if sign_up.is_handler}
                                {t("Handler")}
                            {:else}
                                <i class="text-muted">{t("No costume picked yet")}</i>
                            {/if}
                            {#if sign_up.backup}
                                <span class="d-block text-muted small">
                                    {t("Backup: :costume", { costume: sign_up.backup })}
                                </span>
                            {/if}
                        </span>
                        <span class="my-signup-summary__change">
                            {#if sign_up.can_change}
                                <a href="#roster-row-{sign_up.id}" class="small" onclick={(e) => {
                                    e.preventDefault();
                                    void vm.editSignUp(sign_up.id, sign_up.shift_id);
                                }}>{t("Change")}</a>
                            {/if}
                        </span>
                    </li>
                {/each}
            </ul>
        </div>
    </div>
{:else if summary.has_open_shift}
    <div class="my-signup-summary card mb-3 d-none d-md-block">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
            <span>{t("You're not signed up for this event yet.")}</span>
            <a href="#mission-roster" class="btn btn-sm btn-outline-success ms-auto" onclick={(e) => {
                e.preventDefault();
                void vm.goToSignUp(summary.open_shift_ids);
            }}>{t("Sign up")}</a>
        </div>
    </div>
{/if}

{#if summary.has_open_shift}
    <div class="my-signup-bar d-md-none">
        {#if first}
            <span class="my-signup-bar__status">
                <SignUpStatus status={first.status} />
                {#if active.length > 1}
                    <span class="text-muted small">
                        {t("+ :count more shift|+ :count more shifts", { count: active.length - 1 }, active.length - 1)}
                    </span>
                {/if}
            </span>
            <a href="#roster-row-{first.id}" class="btn btn-sm btn-outline-primary" onclick={(e) => {
                e.preventDefault();
                void vm.editSignUp(first.id, first.shift_id);
            }}>{t("Your sign-up")}</a>
        {:else}
            <span class="my-signup-bar__status text-muted">{t("Not signed up")}</span>
            <a href="#mission-roster" class="btn btn-sm btn-outline-success" onclick={(e) => {
                e.preventDefault();
                void vm.goToSignUp(summary.open_shift_ids);
            }}>{t("Sign up")}</a>
        {/if}
    </div>
{/if}
