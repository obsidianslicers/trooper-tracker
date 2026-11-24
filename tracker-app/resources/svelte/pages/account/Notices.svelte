<script lang="ts">
    import { t } from "$lib/i18n";
    import NoticeCard from "$lib/components/NoticeCard.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import { NoticesViewModel, type NoticesPageData } from "./models";

    const page = usePage<NoticesPageData>();

    pageState.title = t("Trooper Notices");

    const vm = new NoticesViewModel(page.props);
</script>

{#each vm.notices as notice (notice.id)}
    <NoticeCard {notice} ondismiss={() => vm.dismiss(notice)} />
{:else}
    <p class="text-muted">{t("You have no unread notices.")}</p>
{/each}
