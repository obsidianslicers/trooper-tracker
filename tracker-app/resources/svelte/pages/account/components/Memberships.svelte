<script lang="ts">
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import type { MembershipsPageData } from "../models";
    import { MembershipsViewModel } from "../models";
    import MembershipDisplay from "./MembershipDisplay.svelte";
    import MembershipRequests from "./MembershipRequests.svelte";
    import MembershipSelection from "./MembershipSelection.svelte";

    interface Props {
        is_visitor: boolean;
        memberships: MembershipsPageData;
    }
    let { is_visitor, memberships }: Props = $props();

    let vm = new MembershipsViewModel(is_visitor, memberships);
</script>

<SlimView>
    <div class="mb-5">
        <MembershipDisplay {vm} />
    </div>
    <div class="mb-5">
        <MembershipRequests {vm} />
    </div>
    <div class="mb-5">
        <MembershipSelection {vm} />
    </div>
</SlimView>
