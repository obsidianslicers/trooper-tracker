<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventShift, EventSummary, ListEventsViewModel } from "../models";

    let {
        vm,
        event,
        shift,
        show_dates = false,
    }: { vm: ListEventsViewModel; event: EventSummary; shift: EventShift; show_dates?: boolean } = $props();

    const shift_state = $derived(vm.shiftState(event, shift));
    const fill = $derived(vm.shiftFill(event, shift));
    const going = $derived(
        [
            shift.troopers ? t(":count trooper|:count troopers", {}, shift.troopers) : null,
            shift.handlers ? t(":count handler|:count handlers", {}, shift.handlers) : null,
            shift.guests ? t(":count guest|:count guests", {}, shift.guests) : null,
        ]
            .filter(Boolean)
            .join(" · "),
    );
</script>

<li class="list-group-item px-0 position-relative" style="z-index: 2;">
    <a href={shift.url} class="d-block text-decoration-none text-reset">
        <div class="d-flex justify-content-between align-items-center gap-2">
            <span>
                <i class="fa fa-fw fa-clock text-muted me-1"></i>
                {#if show_dates}<span class="text-muted">{shift.date} ·</span>{/if}
                {t(":starts - :ends", { starts: shift.starts, ends: shift.ends })}
            </span>
            <span class="small fw-bold text-end">
                {#if shift_state === "full"}
                    <span class="text-success">
                        {t("Full")}<i class="fa fa-fw fa-check-circle ms-1"></i>
                    </span>
                {:else if shift_state === "empty"}
                    <span class="text-danger">{t("Needs troopers")}</span>
                {:else}
                    {going}
                {/if}
            </span>
        </div>
        {#if fill !== null}
            <div
                class="progress mt-1"
                style="height: 4px;"
                role="progressbar"
                aria-label={t(":going of :allowed troopers", { going: shift.troopers, allowed: event.troopers_allowed ?? 0 })}
                aria-valuenow={fill}
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <div class={["progress-bar", shift_state === "full" ? "bg-success" : "bg-info"]} style="width: {fill}%;"></div>
            </div>
        {/if}
        {#if shift.my_status_label}
            <span class="badge text-bg-success mt-1">
                {t("You: :status", { status: shift.my_status_label })}
            </span>
        {/if}
    </a>
</li>
