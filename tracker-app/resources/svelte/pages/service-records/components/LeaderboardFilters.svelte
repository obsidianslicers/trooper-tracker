<!--
    The leaderboards' club picker and period buttons (All Time, 30D, 60D, ...).
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { LeaderboardFilters } from "../models";
    import { LeaderboardFiltersViewModel } from "../models";

    interface Props {
        filters: LeaderboardFilters;
        route: "service-records.leaderboard" | "service-records.costume";
        params?: Record<string, number>;
    }
    let { filters, route, params = {} }: Props = $props();

    const vm = $derived(new LeaderboardFiltersViewModel(filters, route, params));
</script>

<div class="d-flex flex-wrap gap-3 align-items-end">
    <div>
        <label class="form-label" for="organization_id">{t("Club")}</label>
        <select
            id="organization_id"
            name="organization_id"
            class="form-select"
            value={vm.organizationId}
            onchange={(e) => vm.pickClub(e.currentTarget.value)}
        >
            <option value="">{t("All Clubs")}</option>
            {#each filters.organizations as option (option.value)}
                <option value={String(option.value)}>{option.label}</option>
            {/each}
        </select>
    </div>

    <div class="btn-group shadow-sm">
        <a href={vm.url(null)} class={["btn btn-sm btn-outline-dark", vm.days === null && "active"]}>
            {t("All Time")}
        </a>
        {#each vm.dayOptions as interval (interval)}
            <a
                href={vm.url(interval)}
                class={["btn btn-sm btn-outline-dark", vm.days === interval && "active"]}
            >
                {t(":daysD", { days: interval })}
            </a>
        {/each}
    </div>
</div>
