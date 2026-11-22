<script lang="ts">
    import { t } from "$lib/i18n";
    import TrooperStamps from "$lib/components/TrooperStamps.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import NoticeForm from "./components/NoticeForm.svelte";
    import { NoticeFormViewModel, type NoticeFormPageData } from "./models";

    const page = usePage<NoticeFormPageData>();

    pageState.title = t("Update Notice");

    let vm = new NoticeFormViewModel(page.props);
</script>

<SlimView wide={true}>
    <NoticeForm {vm} label={t("Update")} />
    <TrooperStamps trooper_stamps={vm.trooper_stamps} />
</SlimView>
