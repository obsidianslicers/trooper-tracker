<!--
    The event page: details, each shift's roster with Sign Up, the viewer's own sign-ups, photos,
    and what plugins add. Each change reloads only the parts of the page it touched.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import ConfirmationModal from "$lib/components/ConfirmationModal.svelte";
    import PluginSlot from "$lib/components/PluginSlot.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import { untrack } from "svelte";
    import CharitySection from "./components/display/CharitySection.svelte";
    import ContactSection from "./components/display/ContactSection.svelte";
    import DeploymentLimits from "./components/display/DeploymentLimits.svelte";
    import EventHeader from "./components/display/EventHeader.svelte";
    import LogisticsSection from "./components/display/LogisticsSection.svelte";
    import MissionBrief from "./components/display/MissionBrief.svelte";
    import MissionRoster from "./components/display/MissionRoster.svelte";
    import MySignUpSummary from "./components/display/MySignUpSummary.svelte";
    import NotCountedNotice from "./components/display/NotCountedNotice.svelte";
    import OneTroopPerDayNotice from "./components/display/OneTroopPerDayNotice.svelte";
    import RequestedCharacters from "./components/display/RequestedCharacters.svelte";
    import ShareButtons from "./components/display/ShareButtons.svelte";
    import TrooperQuotas from "./components/display/TrooperQuotas.svelte";
    import UploadsPanel from "./components/display/UploadsPanel.svelte";
    import VenueSection from "./components/display/VenueSection.svelte";
    import { EventDisplayViewModel, type EventDisplayPageData } from "./models";

    const page = usePage<EventDisplayPageData & { user: { display_name: string | null } | null }>();

    pageState.title = t("Event Details");

    const vm = new EventDisplayViewModel(untrack(() => page.props.shifts));

    $effect(() => vm.sync(page.props.shifts));

    const event = $derived(page.props.event);
    const slots = $derived(page.props.event_slots ?? {});
</script>

<div class="col-12 col-xl-10 col-xxl-9 m-auto mb-5 p-0">
    <div class="my-4">
        <div class="card">
            <EventHeader {event} />
            <div class="card-body">
                <NotCountedNotice notice={event.not_counted_notice} />
                <OneTroopPerDayNotice notice={event.one_troop_per_day_notice} />

                <div class="row mb-2 border-bottom">
                    <div class="col-12 col-md-6 small text-muted pb-3">
                        <div class="row">
                            <div class="col-4 col-md-5 col-lg-4 text-nowrap">{t("Hosted By:")}</div>
                            <div class="col-8 col-md-7 col-lg-8">{event.organization_name ?? ""}</div>
                        </div>
                        <div class="row">
                            <div class="col-4 col-md-5 col-lg-4 text-nowrap">{t("Status:")}</div>
                            <div class="col-8 col-md-7 col-lg-8">{event.status_label}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 text-end">
                        <ShareButtons calendar={event.calendar} shares={event.shares}
                                      share_buttons_html={slots["events.display.share-buttons"] ?? ""}
                                      is_watching={page.props.is_watching} watch_url={page.props.tools.watch_url} />
                    </div>
                </div>

                <div class="row pb-3 mb-3 border-bottom">
                    <div class="col-12">
                        {event.when}
                        {#if event.shift_count > 1}
                            <span class="text-muted d-block d-md-inline ps-0 ps-md-3 pt-1 pt-md-0">
                                ** {t(":count shift available|:count shifts available", { count: event.shift_count }, event.shift_count)} **
                            </span>
                        {/if}
                    </div>
                </div>

                <MySignUpSummary {vm} summary={page.props.my_sign_ups} />

                <div class="row">
                    <div class="col-12 col-md-6 mb-3"><VenueSection venue={event.venue} /></div>
                    <div class="col-12 col-md-6 mb-3">
                        <ContactSection contact={event.contact} coordinator={event.coordinator} />
                    </div>
                </div>

                {#if event.mission_brief}
                    {#key event.mission_brief.html}
                        <MissionBrief brief={event.mission_brief} />
                    {/key}
                {/if}

                <div class="row">
                    <div class="col-12 col-md-6 mb-3"><LogisticsSection logistics={event.logistics} /></div>
                    <div class="col-12 col-md-6 mb-3"><TrooperQuotas quotas={event.quotas} /></div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3"><DeploymentLimits limits={event.limits} /></div>
                    <div class="col-12 col-md-6 mb-3"><RequestedCharacters requested={event.requested_characters} /></div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3"><CharitySection charity={event.charity} /></div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <MissionRoster {vm} shifts={page.props.shifts} tools={page.props.tools} tour={page.props.tour}
                                       not_counted_notice={event.not_counted_notice}
                                       viewer_name={page.props.user?.display_name ?? ""} />
                    </div>
                </div>

                <PluginSlot name="events.display.sections" html={slots["events.display.sections"] ?? ""} />

                <UploadsPanel {vm} uploads={page.props.uploads}
                              default_instructional={event.uploads_default_to_instructional} />

                <PluginSlot name="events.display.discussion" html={slots["events.display.discussion"] ?? ""} />

                <!-- room for the phones' sign-up bar -->
                <div class="my-signup-bar-spacer d-md-none"></div>
            </div>
        </div>
    </div>
</div>

<ConfirmationModal
    show={vm.confirmation !== null}
    title={vm.confirmation?.title}
    message={vm.confirmation?.message}
    confirmLabel={vm.confirmation?.label}
    danger={vm.confirmation?.danger ?? false}
    onConfirm={vm.runConfirmation}
    onCancel={vm.cancelConfirmation}
/>
