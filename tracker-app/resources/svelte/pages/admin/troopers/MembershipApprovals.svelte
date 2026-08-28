<script lang="ts">
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import TrooperApprovals from "./components/TrooperApprovals.svelte";
    import TrooperRequests from "./components/TrooperRequests.svelte";
    import type { MembershipApprovalsPageData } from "./models";
    import { MembershipApprovalsViewModel } from "./models";

    pageState.title = "Trooper Approvals";

    const page = usePage<MembershipApprovalsPageData>();

    let vm = $derived(new MembershipApprovalsViewModel(page.props));
</script>

<TrooperApprovals {vm} />
<TrooperRequests {vm} />
