<script lang="ts">
    import { t } from "$lib/i18n";
    import Alert from "$lib/components/ui/Alert.svelte";
    import Tab from "$lib/components/ui/tabs/Tab.svelte";
    import TabContent from "$lib/components/ui/tabs/TabContent.svelte";
    import TabHeader from "$lib/components/ui/tabs/TabHeader.svelte";
    import TabPanel from "$lib/components/ui/tabs/TabPanel.svelte";
    import Tabs from "$lib/components/ui/tabs/Tabs.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import CharityTab from "./components/CharityTab.svelte";
    import OverviewTab from "./components/OverviewTab.svelte";
    import RosterTab from "./components/RosterTab.svelte";
    import ShiftsTab from "./components/ShiftsTab.svelte";
    import UploadsTab from "./components/UploadsTab.svelte";
    import type { UpdateEventPageData } from "./models";

    const page = usePage<UpdateEventPageData>();

    // Commands answer with the refreshed page data as `results` (partial reload).
    let data = $derived<UpdateEventPageData>(page.props.results ?? page.props);

    pageState.title = t("Update Event");

    const tabs = ["overview", "shifts", "charity", "roster", "uploads", "mission-review"];
    const requested_tab = new URLSearchParams(page.url.split("?")[1] ?? "").get("tab");
    const default_tab = tabs.includes(requested_tab ?? "") ? requested_tab : "overview";
</script>

<h4 class="mb-1">{data.name}</h4>
<p class="text-muted small mb-3">
    {t("Status: :status", { status: data.status_label })}
    &middot;
    <a href={data.overview.display_url} target="_blank">
        {t("View Event")}
        <span class="fa fa-fw fa-external-link"></span>
    </a>
</p>

{#if data.is_draft}
    <Alert type="warning">
        {t("This event is currently in Draft status and not visible to troopers.")}
    </Alert>
{/if}

<Tabs defaultTab={default_tab}>
    <TabHeader>
        <Tab id="overview">{t("Overview")}</Tab>
        <Tab id="shifts">{t("Shifts")}</Tab>
        <Tab id="charity">{t("Charity")}</Tab>
        <Tab id="roster">{t("Roster")}</Tab>
        <Tab id="uploads">{t("Administrative Uploads")}</Tab>
        <Tab id="mission-review">{t("Mission Review")}</Tab>
    </TabHeader>
    <TabContent>
        <TabPanel id="overview">
            <OverviewTab event_id={data.event_id} overview={data.overview} />
        </TabPanel>
        <TabPanel id="shifts">
            <ShiftsTab
                event_id={data.event_id}
                shifts={data.shifts}
                is_active={data.is_active}
                status_options={data.overview.status_options}
            />
        </TabPanel>
        <TabPanel id="charity">
            <CharityTab event_id={data.event_id} shifts={data.charity} />
        </TabPanel>
        <TabPanel id="roster">
            <RosterTab event_id={data.event_id} roster={data.roster} />
        </TabPanel>
        <TabPanel id="uploads">
            <UploadsTab
                event_id={data.event_id}
                uploads={data.uploads}
                is_administrative={true}
            />
        </TabPanel>
        <TabPanel id="mission-review">
            <UploadsTab
                event_id={data.event_id}
                uploads={data.mission_review}
                is_administrative={false}
            />
        </TabPanel>
    </TabContent>
</Tabs>
