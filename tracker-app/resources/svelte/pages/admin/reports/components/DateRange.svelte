<!--
    The first and last day a report covers; either may be left empty.
-->
<script lang="ts">
    import InputDate from "$lib/components/form/InputDate.svelte";
    import { t } from "$lib/i18n";

    interface Props {
        date_start: string | null;
        date_end: string | null;
    }
    let { date_start = $bindable(), date_end = $bindable() }: Props = $props();
</script>

<div class="col-sm-4">
    <InputDate label={t("Date Start")} bind:value={date_start} />
</div>
<div class="col-sm-4">
    <InputDate label={t("Date End")} bind:value={date_end} />
</div>
