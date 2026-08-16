<script lang="ts">
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import type { MembershipsPageData } from "$lib/domains/account";
    import { MembershipsViewModel } from "$lib/domains/account";
    import MembershipDisplay from "./MembershipDisplay.svelte";

    interface Props {
        memberships: MembershipsPageData;
    }
    let { memberships }: Props = $props();

    let vm = new MembershipsViewModel(memberships);
</script>

<SlimView>
    <h6 class="mb-3">Organization Memberships</h6>

    <MembershipDisplay {vm} />
</SlimView>
