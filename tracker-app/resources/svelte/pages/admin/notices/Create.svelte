<script lang="ts">
    import { t } from "$lib/i18n";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import NoticeForm from "./components/NoticeForm.svelte";
    import { NoticeFormViewModel, type NoticeFormPageData } from "./models";

    const page = usePage<NoticeFormPageData>();

    pageState.title = t("Create Notice");

    let vm = new NoticeFormViewModel(page.props);
</script>

<SlimView wide={true}>
    <NoticeForm {vm} label={t("Create")} />
</SlimView>
