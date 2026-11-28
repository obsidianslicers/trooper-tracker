<!--
    A trooper's service record: summary and milestones on the left, their shifts, awards,
    costumes, photos and donations in tabs on the right.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import PluginSlot from "$lib/components/PluginSlot.svelte";
    import type { ServiceRecordPageData } from "./models/record-types";
    import { ServiceRecordViewModel } from "./models/vms/ServiceRecordViewModel.svelte";
    import AwardsTab from "./components/record/AwardsTab.svelte";
    import CostumesTab from "./components/record/CostumesTab.svelte";
    import CreditRequestsTab from "./components/record/CreditRequestsTab.svelte";
    import DonationsTab from "./components/record/DonationsTab.svelte";
    import InMemoriam from "./components/record/InMemoriam.svelte";
    import PendingConfirmations from "./components/record/PendingConfirmations.svelte";
    import PhotosTab from "./components/record/PhotosTab.svelte";
    import RecentShifts from "./components/record/RecentShifts.svelte";
    import RecordHeader from "./components/record/RecordHeader.svelte";
    import RecordTabs from "./components/record/RecordTabs.svelte";
    import ServiceSummary from "./components/record/ServiceSummary.svelte";
    import UpcomingShifts from "./components/record/UpcomingShifts.svelte";

    let props: ServiceRecordPageData = $props();

    pageState.title = t("Service Record");

    // svelte-ignore state_referenced_locally
    const vm = new ServiceRecordViewModel(props.trooper, window.innerWidth);
    const slots = $derived(props.record_slots ?? {});
</script>

{#if props.trooper.is_departed}
    <InMemoriam name={props.trooper.display_name} />
{/if}

<RecordHeader trooper={props.trooper} {slots} />

<div class="row g-4">
    <div class="col-lg-4">
        <ServiceSummary summary={props.summary} slot={slots["service-records.trooper.summary"] ?? ""} />
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header border-bottom-0 p-0">
                <RecordTabs
                    current={vm.tab}
                    showUpcoming={!props.trooper.is_departed}
                    showSupport={props.donations !== null}
                    showCreditRequests={props.credit_requests !== null}
                    onselect={(tab) => (vm.tab = tab)}
                />
            </div>
            <div class="card-body">
                {#if vm.tab === "upcoming"}
                    <div id="upcoming-shifts" role="tabpanel">
                        <UpcomingShifts shifts={props.upcoming_shifts} layout={vm.layout} ontoggle={vm.toggleLayout} />
                    </div>
                {:else if vm.tab === "history"}
                    <div id="recent-shifts" role="tabpanel">
                        <PendingConfirmations shifts={props.pending_shifts} />
                        <RecentShifts shifts={props.recent_shifts} layout={vm.layout} ontoggle={vm.toggleLayout} />
                        <PluginSlot name="service-records.trooper.history" html={slots["service-records.trooper.history"] ?? ""} />
                    </div>
                {:else if vm.tab === "awards"}
                    <div id="awards" role="tabpanel">
                        <AwardsTab awards={props.awards} />
                    </div>
                {:else if vm.tab === "costumes"}
                    <div id="costumes" role="tabpanel">
                        <CostumesTab
                            isHandler={props.trooper.is_handler}
                            organizations={props.organizations}
                            costumes={props.costumes}
                        />
                    </div>
                {:else if vm.tab === "photos"}
                    <div id="photos" role="tabpanel">
                        <PhotosTab uploads={props.tagged_uploads} isSelf={props.trooper.is_self} />
                    </div>
                {:else if vm.tab === "credit-requests" && props.credit_requests}
                    <div id="credit-requests" role="tabpanel">
                        <CreditRequestsTab requests={props.credit_requests} isSelf={props.trooper.is_self} />
                    </div>
                {:else if vm.tab === "support" && props.donations}
                    <div id="donations" role="tabpanel">
                        <DonationsTab
                            donations={props.donations}
                            total={props.donations_total}
                            slot={slots["service-records.trooper.donations"] ?? ""}
                        />
                    </div>
                {/if}
            </div>
        </div>
    </div>
</div>
