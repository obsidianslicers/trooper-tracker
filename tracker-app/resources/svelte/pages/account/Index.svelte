<script lang="ts">
    import Tab from "$lib/components/ui/tabs/Tab.svelte";
    import TabContent from "$lib/components/ui/tabs/TabContent.svelte";
    import TabHeader from "$lib/components/ui/tabs/TabHeader.svelte";
    import TabPanel from "$lib/components/ui/tabs/TabPanel.svelte";
    import Tabs from "$lib/components/ui/tabs/Tabs.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import Costumes from "./components/Costumes.svelte";
    import Delete from "./components/Delete.svelte";
    import Details from "./components/Details.svelte";
    import Friends from "./components/Friends.svelte";
    import Memberships from "./components/Memberships.svelte";
    import Minors from "./components/Minors.svelte";
    import Notifications from "./components/Notifications.svelte";
    import {
        AccountViewModel,
        type AccountPageData,
    } from "./models/vms/AccountViewModel.svelte";

    const page = usePage<AccountPageData>();

    pageState.title = "Trooper Account";

    let vm = new AccountViewModel(page.props);
</script>

<Tabs defaultTab="profile">
    <TabHeader>
        <Tab id="profile">Profile</Tab>
        <Tab id="notifications">Notifications</Tab>
        {#if !vm.is_handler}
            <Tab id="costumes">Costumes</Tab>
        {/if}
        <Tab id="memberships">Memberships</Tab>
        {#if vm.has_friends}
            <Tab id="friends">Friends</Tab>
        {/if}
        {#if vm.has_minors}
            <Tab id="minors">Cadets</Tab>
        {/if}
        {#if !vm.has_deletion_request}
            <Tab id="delete">Delete Account</Tab>
        {/if}
    </TabHeader>
    <TabContent>
        <TabPanel id="profile">
            <Details details={vm.pageData?.details} />
        </TabPanel>
        <TabPanel id="notifications">
            <Notifications notifications={vm.pageData?.notifications} />
        </TabPanel>
        <TabPanel id="costumes">
            <Costumes costumes={vm.pageData?.costumes} />
        </TabPanel>
        <TabPanel id="memberships">
            <Memberships
                is_visitor={vm.pageData?.is_visitor}
                memberships={vm.pageData?.memberships}
            />
        </TabPanel>
        <TabPanel id="friends">
            <Friends friends={vm.pageData?.friends} />
        </TabPanel>
        <TabPanel id="minors">
            <Minors minors={vm.pageData?.minors} />
        </TabPanel>
        <TabPanel id="delete">
            <Delete />
        </TabPanel>
    </TabContent>
</Tabs>
