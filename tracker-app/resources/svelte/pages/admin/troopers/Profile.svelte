<script lang="ts">
    import Tab from "$lib/components/ui/tabs/Tab.svelte";
    import TabContent from "$lib/components/ui/tabs/TabContent.svelte";
    import TabHeader from "$lib/components/ui/tabs/TabHeader.svelte";
    import TabPanel from "$lib/components/ui/tabs/TabPanel.svelte";
    import Tabs from "$lib/components/ui/tabs/Tabs.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import ProfileAuthority from "./components/profile/ProfileAuthority.svelte";
    import ProfileChanges from "./components/profile/ProfileChanges.svelte";
    import ProfileCostumes from "./components/profile/ProfileCostumes.svelte";
    import ProfileDetails from "./components/profile/ProfileDetails.svelte";
    import ProfileEvents from "./components/profile/ProfileEvents.svelte";
    import ProfileGuardian from "./components/profile/ProfileGuardian.svelte";
    import ProfileMemberships from "./components/profile/ProfileMemberships.svelte";
    import type { ProfilePageData } from "./models";

    const page = usePage<ProfilePageData>();

    // Commands answer with the refreshed page data as `results` (partial reload).
    let data = $derived<ProfilePageData>(page.props.results ?? page.props);

    pageState.title = page.props.display_name;

    const tabs = [
        "profile",
        "authority",
        "memberships",
        "guardian",
        "costumes",
        "events",
        "changes",
    ];
    const requested_tab = new URLSearchParams(page.url.split("?")[1] ?? "").get("tab");
    const default_tab = tabs.includes(requested_tab ?? "") ? requested_tab : "profile";
</script>

<Tabs defaultTab={default_tab}>
    <TabHeader>
        <Tab id="profile">Profile</Tab>
        {#if data.authority}
            <Tab id="authority">Authority</Tab>
        {/if}
        <Tab id="memberships">Memberships</Tab>
        {#if data.guardian}
            <Tab id="guardian">Guardian</Tab>
        {/if}
        <Tab id="costumes">Costumes</Tab>
        <Tab id="events">Events</Tab>
        <Tab id="changes">Changes</Tab>
    </TabHeader>
    <TabContent>
        <TabPanel id="profile">
            <ProfileDetails trooper_id={data.trooper_id} details={data.details} />
        </TabPanel>
        {#if data.authority}
            <TabPanel id="authority">
                <ProfileAuthority trooper_id={data.trooper_id} authority={data.authority} />
            </TabPanel>
        {/if}
        <TabPanel id="memberships">
            <ProfileMemberships trooper_id={data.trooper_id} memberships={data.memberships} />
        </TabPanel>
        {#if data.guardian}
            <TabPanel id="guardian">
                <ProfileGuardian trooper_id={data.trooper_id} guardian={data.guardian} />
            </TabPanel>
        {/if}
        <TabPanel id="costumes">
            <ProfileCostumes costumes={data.costumes} />
        </TabPanel>
        <TabPanel id="events">
            <ProfileEvents events={data.events} />
        </TabPanel>
        <TabPanel id="changes">
            <ProfileChanges changes={data.changes} />
        </TabPanel>
    </TabContent>
</Tabs>
