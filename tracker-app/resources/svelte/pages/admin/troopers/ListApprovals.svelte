<script lang="ts">
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import TrooperApprovals from "./components/TrooperApprovals.svelte";
    import TrooperRequests from "./components/TrooperRequests.svelte";
    import type { ListApprovalsPageData } from "./models";
    import { ListApprovalsViewModel } from "./models";

    pageState.title = "Trooper Approvals";

    const page = usePage<ListApprovalsPageData>();

    let vm = $derived(new ListApprovalsViewModel(page.props));
</script>

<TrooperApprovals {vm} />
<TrooperRequests {vm} />
