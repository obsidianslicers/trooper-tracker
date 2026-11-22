<!--
    Every shift's roster, with the moderators' tools (CSV, Share roster, Broadcast) and the
    sign-up tour.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { untrack } from "svelte";
    import {
        SignUpTourViewModel,
        signUpTourSteps,
        type DisplayShift,
        type DisplayTools,
        type EventDisplayViewModel,
        type SignUpTourData,
    } from "../../models";
    import BroadcastMessage from "./BroadcastMessage.svelte";
    import NotCountedNotice from "./NotCountedNotice.svelte";
    import SectionTitle from "./SectionTitle.svelte";
    import ShareRosterModal from "./ShareRosterModal.svelte";
    import ShiftCard from "./ShiftCard.svelte";
    import SignUpTour from "./SignUpTour.svelte";

    interface Props {
        vm: EventDisplayViewModel;
        shifts: DisplayShift[];
        tools: DisplayTools;
        tour: SignUpTourData;
        not_counted_notice: string | null;
        viewer_name: string;
    }

    let { vm, shifts, tools, tour, not_counted_notice, viewer_name }: Props = $props();

    let sharing = $state(false);

    const tour_vm = new SignUpTourViewModel(() => signUpTourSteps(tour), untrack(() => tour.storage_key));
</script>

<div id="mission-roster" class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <SectionTitle classes="mb-0">{t("Mission Roster")}</SectionTitle>
    <div class="d-flex flex-wrap align-items-center gap-3">
        {#if tools.roster_csv_url}
            <a class="btn btn-link btn-sm p-0 text-muted d-none d-md-inline" href={tools.roster_csv_url}>
                {t("Download CSV (All Shifts)")}
            </a>
        {/if}
        {#if tools.share_roster_url}
            <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action" onclick={() => (sharing = true)}>
                <i class="fa fa-fw fa-share-nodes me-1"></i>
                {t("Share roster")}
            </button>
        {/if}
        <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action" data-tour-replay
                onclick={tour_vm.start}>
            {t("How sign-up works")}
        </button>
    </div>
</div>

<!-- again where troopers sign up, so nobody signs up without seeing it -->
<NotCountedNotice notice={not_counted_notice} />

<SignUpTour {tour} {viewer_name} vm={tour_vm} />

{#each shifts as shift (shift.id)}
    <ShiftCard {vm} {shift} />
{/each}

{#if tools.share_roster_url}
    <ShareRosterModal {vm} url={tools.share_roster_url} bind:show={sharing} />
{/if}

{#if tools.broadcast_url}
    <BroadcastMessage {vm} url={tools.broadcast_url} going_label={tools.broadcast_status} />
{/if}
