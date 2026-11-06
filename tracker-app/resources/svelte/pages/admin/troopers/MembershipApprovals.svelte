<script lang="ts">
    import PluginSlot from "$lib/components/PluginSlot.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import TrooperApprovals from "./components/membership/TrooperApprovals.svelte";
    import TrooperRequests from "./components/membership/TrooperRequests.svelte";
    import type { MembershipApprovalsPageData } from "./models";
    import { MembershipApprovalsViewModel } from "./models";

    pageState.title = "Trooper Approvals";

    const page = usePage<MembershipApprovalsPageData>();

    let vm = new MembershipApprovalsViewModel(page.props);
</script>

<TrooperApprovals {vm} />
<TrooperRequests {vm} />
<PluginSlot name="admin.troopers.approvals.sections" />
