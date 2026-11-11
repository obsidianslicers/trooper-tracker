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
        {#if page.props.authority}
            <Tab id="authority">Authority</Tab>
        {/if}
        <Tab id="memberships">Memberships</Tab>
        {#if page.props.guardian}
            <Tab id="guardian">Guardian</Tab>
        {/if}
        <Tab id="costumes">Costumes</Tab>
        <Tab id="events">Events</Tab>
        <Tab id="changes">Changes</Tab>
    </TabHeader>
    <TabContent>
        <TabPanel id="profile">
            <ProfileDetails trooper_id={page.props.trooper_id} details={page.props.details} />
        </TabPanel>
        {#if page.props.authority}
            <TabPanel id="authority">
                <ProfileAuthority trooper_id={page.props.trooper_id} authority={page.props.authority} />
            </TabPanel>
        {/if}
        <TabPanel id="memberships">
            <ProfileMemberships trooper_id={page.props.trooper_id} memberships={page.props.memberships} />
        </TabPanel>
        {#if page.props.guardian}
            <TabPanel id="guardian">
                <ProfileGuardian trooper_id={page.props.trooper_id} guardian={page.props.guardian} />
            </TabPanel>
        {/if}
        <TabPanel id="costumes">
            <ProfileCostumes costumes={page.props.costumes} />
        </TabPanel>
        <TabPanel id="events">
            <ProfileEvents events={page.props.events} />
        </TabPanel>
        <TabPanel id="changes">
            <ProfileChanges changes={page.props.changes} />
        </TabPanel>
    </TabContent>
</Tabs>
