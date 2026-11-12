<script lang="ts">
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import type { ProfileMembershipsPageData } from "../../models";
    import ProfileMembership from "./ProfileMembership.svelte";

    interface Props {
        trooper_id: number;
        memberships: ProfileMembershipsPageData;
    }
    let { trooper_id, memberships }: Props = $props();
</script>

<SlimView>
    {#each memberships.organizations as organization (organization.id)}
        <ProfileMembership
            {trooper_id}
            {organization}
            status_options={memberships.status_options}
        />
    {/each}
</SlimView>
