<script lang="ts">
    import { t } from "$lib/i18n";
    import type { ShiftsLayout } from "../../models/vms/ServiceRecordViewModel.svelte";

    let { layout, ontoggle }: { layout: ShiftsLayout; ontoggle: () => void } = $props();
</script>

<div class="mb-3 d-none d-lg-block">
    <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action" onclick={ontoggle}>
        {layout === "cards" ? t("Switch to Table View") : t("Switch to Card View")}
    </button>
</div>
