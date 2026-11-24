<!-- The 30D, 60D, ... buttons that pick how far back a list looks. -->
<script lang="ts">
    import { t } from "$lib/i18n";

    let { days }: { days: number } = $props();

    const INTERVALS = [30, 60, 90, 180, 360];

    const url = (interval: number): string => {
        const params = new URLSearchParams(window.location.search);
        params.set("days", String(interval));

        return `${window.location.pathname}?${params}`;
    };
</script>

<div class="btn-group shadow-sm">
    {#each INTERVALS as interval (interval)}
        <a href={url(interval)} class={["btn btn-sm btn-outline-dark", days === interval && "active"]}>
            {t(":daysD", { days: interval })}
        </a>
    {/each}
</div>
