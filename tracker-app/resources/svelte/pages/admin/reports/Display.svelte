<script lang="ts">
    import Alert from "$lib/components/ui/Alert.svelte";
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import OperationsDashboard from "./components/OperationsDashboard.svelte";
    import ReportCard from "./components/ReportCard.svelte";
    import type { ReportsDashboardPageData } from "./models/types";

    const page = usePage<ReportsDashboardPageData>();

    pageState.title = t("Reports Dashboard");

    let attended = $derived(page.props.attended);

    let reports = $derived([
        {
            label: t("Event Summary"),
            icon: "fa-file-lines",
            href: getRoute("admin.reports.event-summary"),
            description: t("Event Summary (Total count of troopers at all events)"),
        },
        {
            label: t("Trooper Event Summary"),
            icon: "fa-file-lines",
            href: getRoute("admin.reports.trooper-event-summary"),
            description: t(
                "Trooper Event Summary (Report to get amount of events attended by each trooper. You can do all or search by club)",
            ),
        },
        {
            label: t("Troopers Without Activity"),
            icon: "fa-file-lines",
            href: getRoute("admin.reports.troopers-without-activity"),
            description: t(
                "Active Troopers who have not :attended an event in the last 12 months.",
                { attended },
            ),
        },
        {
            label: t("Trooper Status Change Log"),
            icon: "fa-file-lines",
            href: getRoute("admin.reports.status-change-log"),
            description: t(
                "Trooper Status Change Log - Those troopers who :attended an event where their status was updated by someone other than the trooper in action.",
                { attended },
            ),
        },
        {
            label: t("Donation Event Summary"),
            icon: "fa-file-lines",
            href: getRoute("admin.reports.donation-event-summary"),
            description: t(
                "Donation Event Summary (Report showing charity direct/indirect funds raised per event with date range filtering)",
            ),
        },
        {
            label: t("Costume Used Most at Events"),
            icon: "fa-chart-line",
            href: getRoute("admin.reports.costume-event-summary"),
            description: t(
                "Costume used most at Events (Report to see which costume was used the most at events between certain dates)",
            ),
        },
        {
            label: t("Event Types Counts"),
            icon: "fa-chart-line",
            href: getRoute("admin.reports.event-type-count"),
            description: t("Counts of event-types"),
        },
    ]);
</script>

<OperationsDashboard dashboard={page.props} />

<Alert type="info" classes="text-start border border-info rounded-2 py-3 px-4 my-2">
    {t(
        "Report data below will be filtered to align with your current moderation privileges. Should your clearance ever improve, additional results may become available. Until then, thank you for your patience. We admire your initiative, even if the Empire does not.",
    )}
</Alert>

<div class="row row-cols-1 row-cols-md-3 g-4 mt-1 dashboard-cards">
    {#each reports as report (report.href)}
        <ReportCard {...report} />
    {/each}
</div>
