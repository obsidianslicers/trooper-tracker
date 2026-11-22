<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventSummary, ListEventsViewModel } from "../models";
    import ShiftRow from "./ShiftRow.svelte";

    let { vm, event }: { vm: ListEventsViewModel; event: EventSummary } = $props();

    const header = $derived(event.is_locked ? "bg-secondary" : event.is_at_risk ? "bg-danger" : "bg-primary");
    const is_mine = $derived(vm.isMine(event));
    const show_dates = $derived(new Set(event.shifts.map((s) => s.date)).size > 1);
    const map_url = $derived(
        "https://www.google.com/maps/search/?api=1&query=" + encodeURIComponent(event.venue_address ?? ""),
    );
</script>

<div class="col">
    <article class={["card h-100 shadow-sm event-card", { "border-success": is_mine }]}>
        <div class="card-header {header} d-flex align-items-center gap-2 text-white">
            {#if event.is_at_risk}
                <i class="fa fa-fw fa-warning fa-2x"></i>
            {:else if event.not_counted_notice}
                <i
                    class="fa fa-fw fa-circle-info fa-2x text-white not-counted-logo"
                    title={event.not_counted_notice}
                    aria-hidden="true"
                ></i>
            {:else}
                <img src={event.logo_url} alt={event.organization_name ?? t("Logo")} width="32" height="32" />
            {/if}
            <a href={event.url} class="stretched-link text-white text-decoration-none fw-semibold">
                {event.name}
            </a>
        </div>

        <div class="card-body">
            <div class="d-flex gap-3 mb-3">
                <div class="date-tile text-center border rounded px-2 py-1 flex-shrink-0" aria-hidden="true">
                    <div class="small text-uppercase text-muted">{event.weekday}</div>
                    <div class="fs-3 fw-bold lh-1">{event.day}</div>
                    <div class="small text-uppercase">{event.month}</div>
                </div>
                <div class="min-w-0">
                    <div class="visually-hidden">{event.date_label}</div>
                    {#if event.venue_address}
                        <a
                            href={map_url}
                            target="_blank"
                            rel="noopener"
                            class="d-flex align-items-baseline text-decoration-none position-relative small"
                            style="z-index: 2;"
                        >
                            <i class="fa fa-fw fa-location-dot flex-shrink-0 me-1"></i>
                            <span>{event.venue_address}</span>
                        </a>
                    {/if}
                    {#if event.organization_name}
                        <div class="small text-muted mt-1">
                            <i class="fa fa-fw fa-flag me-1"></i>{event.organization_name}
                        </div>
                    {/if}
                    {#if event.is_at_risk || event.is_locked}
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            {#if event.is_at_risk}
                                <span class="badge text-bg-danger">{t("Risk of cancellation")}</span>
                            {/if}
                            {#if event.is_locked}
                                <span class="badge text-bg-secondary">
                                    <i class="fa fa-fw fa-lock"></i>
                                    {t("Sign-ups locked")}
                                </span>
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>

            {#if event.not_counted_notice}
                <div class="not-counted-notice d-flex align-items-baseline gap-2 small mb-3" role="note">
                    <i class="fa fa-fw fa-circle-info flex-shrink-0"></i>
                    <span>{event.not_counted_notice}</span>
                </div>
            {/if}

            {#if event.shifts.length > 1}
                <div class="small text-muted mb-1">
                    {t(":count Shift Available|:count Shifts Available", {}, event.shifts.length)}
                </div>
            {/if}
            <ul class="list-group list-group-flush">
                {#each event.shifts as shift (shift.id)}
                    <ShiftRow {vm} {event} {shift} {show_dates} />
                {/each}
            </ul>
        </div>

        {#if vm.show_allowed_orgs && event.organizations.length > 0}
            <div class="card-footer small allowed-orgs">
                <span class="text-muted me-1">{t("Who can troop:")}</span>
                {#each event.organizations as organization (organization.id)}
                    {#if organization.can_attend}
                        <span class="badge rounded-pill text-bg-success me-1" data-can-attend="1">
                            <i class="fa fa-fw fa-check"></i>{organization.name}
                            <span class="visually-hidden">{t("can attend")}</span>
                        </span>
                    {:else}
                        <span class="badge rounded-pill border border-danger text-danger me-1" data-can-attend="0">
                            <i class="fa fa-fw fa-xmark"></i>{organization.name}
                            <span class="visually-hidden">{t("can't attend")}</span>
                        </span>
                    {/if}
                {/each}
            </div>
        {/if}
    </article>
</div>
