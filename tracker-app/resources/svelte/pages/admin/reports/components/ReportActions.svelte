<!--
    Run the report with the picked filters, or download them all as a CSV file.
-->
<script lang="ts">
    import { t } from "$lib/i18n";

    interface Props {
        csv_url: string;
    }
    let { csv_url }: Props = $props();
</script>

<div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary">{t("Run Report")}</button>
    <a href={csv_url} class="btn btn-outline-secondary" download>{t("Download CSV")}</a>
</div>
