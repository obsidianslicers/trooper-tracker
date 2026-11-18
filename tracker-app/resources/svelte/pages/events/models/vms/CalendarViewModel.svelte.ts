import type { CalendarEvent, CalendarMonth, CalendarPageData } from "../types";
import { EventFiltersViewModel } from "./EventFiltersViewModel.svelte";

export class CalendarViewModel extends EventFiltersViewModel {
    months: CalendarMonth[] = [];
    weekdays: string[] = [];
    today: string = "";
    events: CalendarEvent[] = [];
    holidays: Record<string, string[]> = {};

    constructor(page_data: Partial<CalendarPageData>) {
        super(page_data);

        this.months = page_data.months ?? [];
        this.weekdays = page_data.weekdays ?? [];
        this.today = page_data.today ?? "";
        this.events = page_data.events ?? [];
        this.holidays = page_data.holidays ?? {};
    }

    filtered: CalendarEvent[] = $derived.by(() => this.events.filter((event) => this.matches(event)));

    by_date: Record<string, CalendarEvent[]> = $derived.by(() => {
        const days: Record<string, CalendarEvent[]> = {};

        for (const event of this.filtered) {
            (days[event.date] ??= []).push(event);
        }

        return days;
    });

    eventsOn(date: string): CalendarEvent[] {
        return this.by_date[date] ?? [];
    }

    holidaysOn(date: string): string[] {
        return this.holidays[date] ?? [];
    }

    countIn(month: CalendarMonth): number {
        const prefix = month.key + "-";

        return this.filtered.filter((event) => event.date.startsWith(prefix)).length;
    }
}
