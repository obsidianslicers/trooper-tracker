<script lang="ts">
    import Modal from "$lib/components/ui/Modal.svelte";
    import { t } from "$lib/i18n";
    import type { ListEventsViewModel } from "../models";

    let { vm }: { vm: ListEventsViewModel } = $props();

    const close = () => (vm.show_hosting_picker = false);
</script>

<Modal bind:show={vm.show_hosting_picker} title={t("Filter by squad")} canClose={close}>
    <p class="small text-muted">
        {t("Pick the squads you troop with. Picking a squad also shows events its region and club host.")}
    </p>
    <div class="border rounded p-2 mb-3 hosting-picker">
        {#each vm.hosting_organizations as organization (organization.id)}
            {@const id = String(organization.id)}
            <div class="form-check py-1" style="margin-left: {organization.depth * 1.5}rem;">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="hosting-organization-{id}"
                    checked={vm.hosting_organization_ids.includes(id)}
                    onchange={(e) => vm.toggleHosting(id, e.currentTarget.checked)}
                />
                <label
                    class={["form-check-label", { "fw-bold": organization.depth === 0 }]}
                    for="hosting-organization-{id}"
                >
                    {organization.name}
                </label>
            </div>
        {/each}
    </div>
    <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary" onclick={vm.clearHosting}>
            {t("Show All")}
        </button>
        <button type="button" class="btn btn-primary" onclick={close}>
            {t("Done")}
        </button>
    </div>
</Modal>
