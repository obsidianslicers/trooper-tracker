<!--
    Shows how an autosaving form is doing: saving, saved, or not saved.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { SaveState } from "$lib/domains/autosave.svelte";

    let { state }: { state: SaveState } = $props();
</script>

<span class="save-status small text-nowrap" aria-live="polite">
    {#if state === "saving"}
        <span class="text-muted">
            <i class="fa fa-fw fa-spinner fa-spin"></i>
            {t("Saving...")}
        </span>
    {:else if state === "saved"}
        <span class="text-success">
            <i class="fa fa-fw fa-check"></i>
            {t("Saved")}
        </span>
    {:else if state === "error"}
        <span class="text-danger">
            <i class="fa fa-fw fa-triangle-exclamation"></i>
            {t("Not saved")}
        </span>
    {/if}
</span>
