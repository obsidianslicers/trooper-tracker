<!--
    How many days back the dashboard counts: 30D, 60D, ...
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";

    interface Props {
        days: number;
        lookbacks: number[];
    }
    let { days, lookbacks }: Props = $props();
</script>

<div class="btn-group shadow-sm">
    {#each lookbacks as interval (interval)}
        <a
            href={getRoute("admin.reports.display", { days: interval })}
            class={["btn btn-outline-dark text-uppercase small fw-bold", days === interval && "active"]}
        >
            {t(":daysD", { days: interval })}
        </a>
    {/each}
</div>
