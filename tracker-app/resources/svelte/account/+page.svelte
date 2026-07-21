<script lang="ts">
    import Loading from '$lib/components/ui/Loading.svelte';
    import Tab from '$lib/components/ui/tabs/Tab.svelte';
    import TabContent from '$lib/components/ui/tabs/TabContent.svelte';
    import TabHeader from '$lib/components/ui/tabs/TabHeader.svelte';
    import TabPanel from '$lib/components/ui/tabs/TabPanel.svelte';
    import Tabs from '$lib/components/ui/tabs/Tabs.svelte';
    import type { PageProps } from './$types';
    import Details from './Details.svelte';
    import Notifications from './Notifications.svelte';

    let { data }: PageProps = $props();
</script>

{#await data.vm}
    <Loading />
{:then vm}
    <Tabs defaultTab="profile">
        <!-- Tab Navigation Header -->
        <TabHeader>
            <Tab id="profile">Profile</Tab>
            <Tab id="notifications">Notifications</Tab>
            <Tab id="costumes">Costumes</Tab>
            <Tab id="memberships">Memberships</Tab>
            <Tab id="friends">Friends</Tab>
        </TabHeader>
        <TabContent>
            <TabPanel id="profile">
                <Details details={vm.account.details} />
            </TabPanel>
            <TabPanel id="notifications">
                <Notifications notifications={vm.account.notifications} />
            </TabPanel>
            <TabPanel id="costumes">
                <h3>Costumes</h3>
                <p>Manage your costume settings here.</p>
            </TabPanel>
            <TabPanel id="memberships">
                <h3>Memberships</h3>
                <p>Manage your membership settings here.</p>
            </TabPanel>
            <TabPanel id="friends">
                <h3>Friends</h3>
                <p>Manage your friends settings here.</p>
            </TabPanel>
        </TabContent>
    </Tabs>
{/await}
