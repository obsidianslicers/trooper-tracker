<script lang="ts">
    import type { CalendarDay, CalendarViewModel } from "../models";

    let { vm, day }: { vm: CalendarViewModel; day: CalendarDay } = $props();

    const events = $derived(day.in_month ? vm.eventsOn(day.date) : []);
    const holidays = $derived(day.in_month ? vm.holidaysOn(day.date) : []);
    const is_today = $derived(day.date === vm.today);
</script>

<td data-event-date={day.date} class={{ "calendar-other-month": !day.in_month, "calendar-today": is_today }}>
    <div class="calendar-content">
        <div class={["calendar-date float-end fw-bold small", { "text-muted": !day.in_month }]}>
            <span class="d-md-none">{day.weekday}</span>
            {#if is_today}
                <span class="badge rounded-pill text-bg-primary">{day.day}</span>
            {:else}
                {day.day}
            {/if}
        </div>
        <br />

        {#each holidays as holiday (holiday)}
            <div class="calendar-holiday small mt-1">
                <i class="fa fa-fw fa-star"></i>
                {holiday}
            </div>
        {/each}

        {#each events as event (event.id)}
            <a
                href={event.url}
                class={[
                    "event-card d-block small mt-1 p-1 rounded text-decoration-none text-reset border",
                    event.is_mine ? "border-success" : event.is_at_risk ? "border-danger" : "border-secondary",
                ]}
                title={event.venue_address ?? event.name}
            >
                <img src={event.logo_url} alt="" width="16" height="16" />
                <span class="text-muted">{event.starts}</span>
                <span>{event.name}</span>
                {#if event.is_mine}<i class="fa fa-fw fa-user-check text-success"></i>{/if}
                {#if event.not_counted_notice}
                    <span class="not-counted-icon" title={event.not_counted_notice}>
                        <i class="fa fa-fw fa-circle-info" aria-hidden="true"></i>
                        <span class="visually-hidden">{event.not_counted_notice}</span>
                    </span>
                {/if}
            </a>
        {/each}
    </div>
</td>
