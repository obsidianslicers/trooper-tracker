<script lang="ts">
    import { t } from "$lib/i18n";
    import ConfirmationModal from "$lib/components/ConfirmationModal.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import AwardSummary from "./components/AwardSummary.svelte";
    import AwardTabs from "./components/AwardTabs.svelte";
    import { TroopersViewModel, type TroopersPageData } from "./models";

    const page = usePage<TroopersPageData>();

    pageState.title = t("Troopers Awarded");

    let vm = new TroopersViewModel(page.props);
</script>

<AwardTabs award_id={vm.award.id!} active="troopers" />

<SlimView wide={true}>
    <AwardSummary award={vm.award} />

    <div class="text-end mb-3">
        <a href={vm.assign_url} class="btn btn-outline-primary">{t("Award Trooper")}</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>{t("Trooper")}</th>
                    <th>{t("Awarded")}</th>
                    <th class="text-end">{t("Actions")}</th>
                </tr>
            </thead>
            <tbody>
                {#each vm.troopers as trooper (trooper.award_trooper_id)}
                    <tr>
                        <td>{trooper.display_name}</td>
                        <td>{trooper.award_date}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm"
                                onclick={() => vm.confirmRemove(trooper)}
                            >
                                {t("Remove")}
                            </button>
                        </td>
                    </tr>
                {:else}
                    <tr>
                        <td colspan="3" class="text-muted">{t("No troopers have this award yet.")}</td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
</SlimView>

<ConfirmationModal
    show={vm.removing !== null}
    title={t("Remove Award")}
    message={t("Remove this award from :name?", { name: vm.removing?.display_name ?? "" })}
    confirmLabel={t("Remove")}
    danger={true}
    submitting={vm.submitting}
    onConfirm={vm.remove}
    onCancel={vm.cancelRemove}
/>
