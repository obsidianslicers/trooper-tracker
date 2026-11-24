<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";
    import NoticeCard from "$lib/components/NoticeCard.svelte";
    import type { ListEventsViewModel } from "../models";

    let { vm }: { vm: ListEventsViewModel } = $props();
</script>

{#if vm.notices.notice}
    <NoticeCard notice={vm.notices.notice} ondismiss={vm.dismissNotice} />
{:else if vm.notices.count > 1}
    <div class="alert alert-info" role="status">
        <i class="fa fa-fw fa-solid fa-circle-info"></i>
        <a href={getRoute("account.notices")}>
            {t("You have :count unread notices.", { count: vm.notices.count })}
        </a>
    </div>
{/if}
