import { getCookie, setCookie } from "$lib/cookies";
import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type {
    EventMonth,
    EventShift,
    EventSummary,
    HostingOrganization,
    ListEventsPageData,
    Notices,
    OrganizationOption,
} from "../types";

//  Shared with the calendar, completed and cancelled pages, so a filter set here sticks there.
export const COOKIES = {
    hosting: "hosting_organization_ids",
    costume: "costume_organization_id",
    allowed_orgs: "display_allowed_orgs",
    only_mine: "events_only_mine",
    needs_troopers: "events_needs_troopers",
};

export type ShiftState = "full" | "empty" | "going";

export class ListEventsViewModel extends ViewModel {
    events: EventSummary[] = $state([]);
    costume_organizations: OrganizationOption[] = [];
    hosting_organizations: HostingOrganization[] = [];
    notices: Notices = $state({ count: 0, notice: null });
    has_map: boolean = false;

    search_term: string = $state("");
    costume_organization_id: string = $state("");
    hosting_organization_ids: string[] = $state([]);
    show_allowed_orgs: boolean = $state(true);
    only_mine: boolean = $state(false);
    needs_troopers: boolean = $state(false);
    show_hosting_picker: boolean = $state(false);

    private parents: Record<string, string> = {};

    constructor(page_data: Partial<ListEventsPageData>) {
        super();

        this.events = page_data.events ?? [];
        this.costume_organizations = page_data.costume_organizations ?? [];
        this.hosting_organizations = page_data.hosting_organizations ?? [];
        this.notices = page_data.notices ?? { count: 0, notice: null };
        this.has_map = page_data.has_map ?? false;

        for (const organization of this.hosting_organizations) {
            if (organization.parent_id !== null) {
                this.parents[String(organization.id)] = String(organization.parent_id);
            }
        }

        this.restore();
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

    get has_hosting_filter(): boolean {
        const selected = this.hosting_organization_ids.length;

        return selected > 0 && selected < this.hosting_organizations.length;
    }

    get active_hosting_ids(): string[] {
        return this.has_hosting_filter ? this.hosting_organization_ids : [];
    }

    get has_filters(): boolean {
        return this.search_term.trim().length > 0
            || this.costume_organization_id !== ""
            || this.has_hosting_filter
            || this.only_mine
            || this.needs_troopers;
    }

    get costume_organization_name(): string {
        const id = Number(this.costume_organization_id);

        return this.costume_organizations.find((o) => o.value === id)?.label ?? "";
    }

    hostingName = (id: string): string => {
        return this.hosting_organizations.find((o) => String(o.id) === id)?.name ?? "";
    };

    matches(event: EventSummary): boolean {
        const term = this.search_term.trim().toLowerCase();

        if (term && !event.name.toLowerCase().includes(term)
            && !(event.venue_address ?? "").toLowerCase().includes(term)) {
            return false;
        }

        const hosts = this.matchingHostIds();

        if (hosts.size > 0 && !hosts.has(String(event.organization_id ?? ""))) {
            return false;
        }

        if (this.costume_organization_id) {
            const id = Number(this.costume_organization_id);

            if (!event.organizations.some((o) => o.id === id && o.can_attend)) {
                return false;
            }
        }

        if (this.only_mine && !this.isMine(event)) {
            return false;
        }

        if (this.needs_troopers && !this.needsTroopers(event)) {
            return false;
        }

        return true;
    }

    //  Events flow upward: picking a unit also shows events hosted by its region and club.
    matchingHostIds(): Set<string> {
        const ids = new Set<string>();

        for (const selected of this.active_hosting_ids) {
            let id: string | undefined = selected;

            while (id && !ids.has(id)) {
                ids.add(id);
                id = this.parents[id];
            }
        }

        return ids;
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

    setSearch = (value: string) => {
        this.search_term = value;
    };

    setCostumeOrganization = (value: string) => {
        this.costume_organization_id = value;
        setCookie(COOKIES.costume, value);
    };

    toggleHosting = (id: string, checked: boolean) => {
        const ids = new Set(this.hosting_organization_ids);

        if (checked) {
            ids.add(id);
        } else {
            ids.delete(id);
        }

        this.hosting_organization_ids = [...ids];
        this.persistHosting();
    };

    removeHosting = (id: string) => {
        this.toggleHosting(id, false);
    };

    clearHosting = () => {
        this.hosting_organization_ids = [];
        this.persistHosting();
    };

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

    clearFilters = () => {
        this.search_term = "";
        this.setCostumeOrganization("");
        this.clearHosting();

        if (this.only_mine) {
            this.toggleOnlyMine();
        }

        if (this.needs_troopers) {
            this.toggleNeedsTroopers();
        }
    };

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

    private persistHosting(): void {
        setCookie(COOKIES.hosting, this.has_hosting_filter ? this.hosting_organization_ids.join(",") : "");
    }

    private restore(): void {
        const known = new Set(this.hosting_organizations.map((o) => String(o.id)));
        const hosting = getCookie(COOKIES.hosting) ?? getCookie("hosting_organization_id") ?? "";

        this.hosting_organization_ids = hosting.split(",")
            .map((id) => id.trim())
            .filter((id) => known.has(id));

        const costume = getCookie(COOKIES.costume) ?? "";
        const costume_known = this.costume_organizations.some((o) => String(o.value) === costume);

        this.costume_organization_id = costume_known ? costume : "";
        this.show_allowed_orgs = (getCookie(COOKIES.allowed_orgs) ?? "1") === "1";
        this.only_mine = getCookie(COOKIES.only_mine) === "1";
        this.needs_troopers = getCookie(COOKIES.needs_troopers) === "1";
    }
}
