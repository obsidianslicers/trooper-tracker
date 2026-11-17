import { getCookie, setCookie } from "$lib/cookies";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type {
    EventMonth,
    EventShift,
    EventSummary,
    ListEventsPageData,
    Notices,
} from "../types";
import { EventFiltersViewModel, HOSTING_COOKIE } from "./EventFiltersViewModel.svelte";

export const COOKIES = {
    hosting: HOSTING_COOKIE,
    allowed_orgs: "display_allowed_orgs",
    only_mine: "events_only_mine",
    needs_troopers: "events_needs_troopers",
};

export type ShiftState = "full" | "empty" | "going";

export class ListEventsViewModel extends EventFiltersViewModel {
    events: EventSummary[] = $state([]);
    notices: Notices = $state({ count: 0, notice: null });

    show_allowed_orgs: boolean = $state(true);
    only_mine: boolean = $state(false);
    needs_troopers: boolean = $state(false);

    constructor(page_data: Partial<ListEventsPageData>) {
        super(page_data);

        this.events = page_data.events ?? [];
        this.notices = page_data.notices ?? { count: 0, notice: null };

        this.show_allowed_orgs = (getCookie(COOKIES.allowed_orgs) ?? "1") === "1";
        this.only_mine = getCookie(COOKIES.only_mine) === "1";
        this.needs_troopers = getCookie(COOKIES.needs_troopers) === "1";
    }

    filtered: EventSummary[] = $derived.by(() => this.events.filter((event) => this.matches(event)));

    months: EventMonth[] = $derived.by(() => {
        const months: EventMonth[] = [];

        for (const event of this.filtered) {
            let month = months[months.length - 1];

            if (!month || month.key !== event.month_key) {
                month = { key: event.month_key, label: event.month_label, events: [] };
                months.push(month);
            }

            month.events.push(event);
        }

        return months;
    });

    get my_event_count(): number {
        return this.events.filter((event) => this.isMine(event)).length;
    }

    get has_filters(): boolean {
        return super.has_filters
            || this.only_mine
            || this.needs_troopers;
    }

    override matches(event: EventSummary): boolean {
        if (!super.matches(event)) {
            return false;
        }

        if (this.only_mine && !this.isMine(event)) {
            return false;
        }

        if (this.needs_troopers && !this.needsTroopers(event)) {
            return false;
        }

        return true;
    }

    isMine(event: EventSummary): boolean {
        return event.shifts.some((shift) => shift.my_status !== null);
    }

    myStatusLabel(event: EventSummary): string | null {
        return event.shifts.find((shift) => shift.my_status !== null)?.my_status_label ?? null;
    }

    needsTroopers(event: EventSummary): boolean {
        return event.is_at_risk || event.shifts.some((shift) => this.shiftState(event, shift) === "empty");
    }

    shiftState(event: EventSummary, shift: EventShift): ShiftState {
        if (event.troopers_allowed !== null && shift.troopers >= event.troopers_allowed) {
            return "full";
        }

        if (shift.troopers + shift.handlers + shift.guests === 0) {
            return "empty";
        }

        return "going";
    }

    shiftFill(event: EventSummary, shift: EventShift): number | null {
        if (!event.troopers_allowed) {
            return null;
        }

        return Math.min(100, Math.round((shift.troopers / event.troopers_allowed) * 100));
    }

    toggleAllowedOrgs = () => {
        this.show_allowed_orgs = !this.show_allowed_orgs;
        setCookie(COOKIES.allowed_orgs, this.show_allowed_orgs ? "1" : "0");
    };

    toggleOnlyMine = () => {
        this.only_mine = !this.only_mine;
        setCookie(COOKIES.only_mine, this.only_mine ? "1" : "0");
    };

    toggleNeedsTroopers = () => {
        this.needs_troopers = !this.needs_troopers;
        setCookie(COOKIES.needs_troopers, this.needs_troopers ? "1" : "0");
    };

    protected override clearOwnFilters(): void {
        if (this.only_mine) {
            this.toggleOnlyMine();
        }

        if (this.needs_troopers) {
            this.toggleNeedsTroopers();
        }
    }

    dismissNotice = () => {
        const notice = this.notices.notice;

        if (!notice) {
            return;
        }

        this.notices = { count: 0, notice: null };

        const url = getRoute("account.update-notice-as-read", { notice: notice.id });

        router.post(url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ["flash", "notices"],
        });
    };
}
