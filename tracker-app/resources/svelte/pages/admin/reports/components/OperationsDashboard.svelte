<!--
    The operations dashboard: strength, attendance and money raised over the lookback window.
-->
<script lang="ts">
    import { tooltip } from "$lib/actions/tooltip";
    import { t } from "$lib/i18n";
    import type { ReportsDashboardPageData } from "../models/types";
    import Lookback from "./Lookback.svelte";
    import StatCard from "./StatCard.svelte";

    let { dashboard }: { dashboard: ReportsDashboardPageData } = $props();

    let personnel = $derived(dashboard.personnel);
</script>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="h4 mb-0 text-uppercase fw-bold">
            <i class="fa-brands fa-empire me-2"></i>
            {t("Operations Dashboard")}
        </h2>
        <Lookback days={dashboard.days} lookbacks={dashboard.lookbacks} />
    </div>

    <div class="row g-3 mb-4">
        <StatCard
            label={t("Active Strength")}
            help={t("Verified troopers currently marked as 'Active'.")}
            icon="fa-users-gear"
            value={personnel.active_count}
            note={t("Mission Ready")}
            accent="border-primary"
        />
        <StatCard
            label={t("Trooper Count")}
            help={t("Total number of troopers in the registry.")}
            icon="fa-address-card"
            value={personnel.trooper_count}
        />
        <StatCard
            label={t("Handler Corps")}
            help={t("Active support staff and handlers.")}
            icon="fa-user-shield"
            value={personnel.handler_count}
            note={t("Support Staff")}
        />
        <StatCard
            label={t("New Enlistments")}
            help={t("New members joined (and activated) within this reporting window.")}
            icon="fa-user-plus"
            value={personnel.new_enlistments}
            tone="text-primary"
        />
    </div>

    <div class="row g-3 mb-5">
        <StatCard
            label={t("Ghost Count")}
            help={t("Active members with no system activity in this reporting window.")}
            icon="fa-ghost"
            value={personnel.ghost_count}
            tone="text-secondary"
        />
        <StatCard
            label={t("Attendance")}
            help={t("Total individual trooper appearances at events.")}
            icon="fa-calendar-check"
            value={personnel.attendance_count}
        />
        <StatCard
            label={t("Attrition Risk")}
            help={t("Active members who did not attend an event in this reporting window.")}
            icon="fa-triangle-exclamation"
            value={personnel.attrition_risk}
            tone="text-danger"
            accent="border-danger"
            danger={true}
        />
        <StatCard
            label={t("Fulfillment")}
            help={t("Percentage of events successfully completed.")}
            icon="fa-check-to-slot"
            value="{dashboard.fulfillment_rate}%"
            tone="text-success"
        />
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-fw fa-solid fa-hand-holding-dollar me-2 text-primary"></i>
                        {t("Galactic Impact")}
                    </h5>
                </div>
                <div class="card-body border-top">
                    <div class="row text-center">
                        <div class="col-4 border-end">
                            <p
                                class="text-muted mb-1 small"
                                use:tooltip={t("Combined direct and indirect funds raised for charity during events.")}
                            >
                                {t("Total Credits")}
                            </p>
                            <h4 class="fw-bold mb-0 text-success">${dashboard.impact.total_credits}</h4>
                        </div>
                        <div class="col-4 border-end">
                            <p
                                class="text-muted mb-1 small"
                                use:tooltip={t("Total volunteer-hours contributed by all participating troopers.")}
                            >
                                {t("Volunteer Hours")}
                            </p>
                            <h4 class="fw-bold mb-0">
                                {t(":hoursh", { hours: dashboard.impact.volunteer_hours })}
                            </h4>
                        </div>
                        <div class="col-4">
                            <p
                                class="text-muted mb-1 small"
                                use:tooltip={t("Donations made internally by troopers to support organization goals.")}
                            >
                                {t("Internal Support")}
                            </p>
                            <h4 class="fw-bold mb-0">${dashboard.impact.internal_donations}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 bg-dark text-white">
                <div class="card-body d-flex flex-column justify-content-around">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span
                            class="small"
                            use:tooltip={t("Number of merit-based awards issued to troopers in this reporting window.")}
                        >
                            <i class="fa-fw fa-solid fa-medal me-2 text-warning"></i>
                            {t("Awards Issued")}
                        </span>
                        <span class="fw-bold">{dashboard.awards_issued}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span
                            class="small"
                            use:tooltip={t("Amount of gallery activity, measured by events with new photo uploads.")}
                        >
                            <i class="fa-fw fa-solid fa-camera me-2 text-info"></i>
                            {t("Photos Tagged")}
                        </span>
                        <span class="fw-bold">{dashboard.photo_activity}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                <i class="fa-fw fa-solid fa-trophy me-2 text-warning"></i>
                {t("Unit Performance Leaderboard")}
            </h5>
            <small class="text-muted">{t("Ranked by Funds Raised")}</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small">
                    <tr>
                        <th class="ps-4" use:tooltip={t("Primary Hosting Organization.")}>
                            {t("Hosting Organization")}
                        </th>
                        <th
                            class="text-center"
                            use:tooltip={t("Successful 'Closed' status events hosted by this organization.")}
                        >
                            {t("Events")}
                        </th>
                        <th
                            class="text-center"
                            use:tooltip={t("Total Trooper count who 'Attended' events hosted by this organization (does not take into account costume worn).")}
                        >
                            {t("Troopers")}
                        </th>
                        <th
                            class="text-end pe-4"
                            use:tooltip={t("Total direct financial contribution attributed to this organization's efforts.")}
                        >
                            {t("Direct")}
                        </th>
                        <th
                            class="text-end pe-4"
                            use:tooltip={t("Total indirect financial contribution attributed to this organization's efforts.")}
                        >
                            {t("Indirect")}
                        </th>
                        <th
                            class="text-end pe-4"
                            use:tooltip={t("Total financial contribution attributed to this organization's efforts.")}
                        >
                            {t("Total")}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {#each dashboard.leaderboard as row (row.name)}
                        <tr>
                            <td class="ps-4 fw-bold text-white">{row.name}</td>
                            <td class="text-center">{row.events_completed}</td>
                            <td class="text-center">{row.troopers_attended}</td>
                            <td class="text-end pe-4 fw-bold text-success">{money(row.direct_funds_raised)}</td>
                            <td class="text-end pe-4 fw-bold text-success">{money(row.indirect_funds_raised)}</td>
                            <td class="text-end pe-4 fw-bold text-success">{money(row.total_funds_raised)}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    </div>
</div>

<script module lang="ts">
    /** The old table showed "$ -" for none; keep the dollar sign on amounts only. */
    function money(value: string): string {
        return value === "-" ? value : `$${value}`;
    }
</script>
