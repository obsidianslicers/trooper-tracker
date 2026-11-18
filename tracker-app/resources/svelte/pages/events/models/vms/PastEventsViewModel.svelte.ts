import type { PastEvent, PastEventsPageData } from "../types";
import { EventFiltersViewModel } from "./EventFiltersViewModel.svelte";

export class PastEventsViewModel extends EventFiltersViewModel {
    events: PastEvent[] = [];
    is_cancelled: boolean = false;
    lookback_days: number | null = null;

    constructor(page_data: Partial<PastEventsPageData>) {
        super(page_data);

        this.events = page_data.events ?? [];
        this.is_cancelled = page_data.status === "cancelled";
        this.lookback_days = page_data.lookback_days ?? null;
    }

    filtered: PastEvent[] = $derived.by(() => this.events.filter((event) => this.matches(event)));
}
