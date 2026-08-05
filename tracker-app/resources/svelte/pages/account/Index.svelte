<script lang="ts">
    import Tab from "$lib/components/ui/tabs/Tab.svelte";
    import TabContent from "$lib/components/ui/tabs/TabContent.svelte";
    import TabHeader from "$lib/components/ui/tabs/TabHeader.svelte";
    import TabPanel from "$lib/components/ui/tabs/TabPanel.svelte";
    import Tabs from "$lib/components/ui/tabs/Tabs.svelte";
    import {
        AccountViewModel,
        type AccountPageData,
    } from "$lib/domains/account/vms/AccountViewModel.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import Details from "./components/Details.svelte";

    const page = usePage<AccountPageData>();

    pageState.title = "Trooper Account";

    let vm = new AccountViewModel(page.props);
</script>

<Tabs defaultTab="profile">
    <TabHeader>
        <Tab id="profile">Profile</Tab>
        <Tab id="notifications">Notifications</Tab>
        <Tab id="costumes">Costumes</Tab>
        <Tab id="memberships">Memberships</Tab>
        <Tab id="friends">Friends</Tab>
    </TabHeader>
    <TabContent>
        <TabPanel id="profile">
            <Details details={vm.pageData?.details} />
        </TabPanel>
        <TabPanel id="notifications">Notifications</TabPanel>
        <TabPanel id="costumes">Costumes</TabPanel>
        <TabPanel id="memberships">Memberships</TabPanel>
        <TabPanel id="friends">Friends</TabPanel>
    </TabContent>
</Tabs>
