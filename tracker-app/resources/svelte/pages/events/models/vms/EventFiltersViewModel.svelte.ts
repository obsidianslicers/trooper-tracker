import { getCookie, setCookie } from "$lib/cookies";
import { ViewModel } from "$lib/domains/types.svelte";
import type { HostingOrganization } from "../types";

//  Shared by every events page, so a squad picked on one sticks on the others.
export const HOSTING_COOKIE = "hosting_organization_ids";

export type FilterableEvent = {
    name: string;
    venue_address?: string | null;
    organization_id: number | null;
};

/**
 * The search box and squad filter every events page shares. Pages add their own filters by
 * extending matches() and has_filters.
 */
export class EventFiltersViewModel extends ViewModel {
    hosting_organizations: HostingOrganization[] = [];
    has_map: boolean = false;

    search_term: string = $state("");
    hosting_organization_ids: string[] = $state([]);
    show_hosting_picker: boolean = $state(false);
    show_filters: boolean = $state(false);

    private parents: Record<string, string> = {};
    private children: Record<string, string[]> = {};

    constructor(page_data: { hosting_organizations?: HostingOrganization[]; has_map?: boolean }) {
        super();

        this.hosting_organizations = page_data.hosting_organizations ?? [];
        this.has_map = page_data.has_map ?? false;

        for (const organization of this.hosting_organizations) {
            if (organization.parent_id !== null) {
                const parent = String(organization.parent_id);

                this.parents[String(organization.id)] = parent;
                (this.children[parent] ??= []).push(String(organization.id));
            }
        }

        this.restoreHosting();
    }

    get has_hosting_filter(): boolean {
        const selected = this.hosting_organization_ids.length;

        return selected > 0 && selected < this.hosting_organizations.length;
    }

    get active_hosting_ids(): string[] {
        return this.has_hosting_filter ? this.hosting_organization_ids : [];
    }

    get has_filters(): boolean {
        return this.search_term.trim().length > 0 || this.has_hosting_filter;
    }

    //  How many filters the phone layout's folded Filters button stands for (search stays visible).
    get filter_count(): number {
        return this.active_hosting_ids.length;
    }

    toggleFilters = () => {
        this.show_filters = !this.show_filters;
    };

    hostingName = (id: string): string => {
        return this.hosting_organizations.find((o) => String(o.id) === id)?.name ?? "";
    };

    matches(event: FilterableEvent): boolean {
        const term = this.search_term.trim().toLowerCase();

        if (term && !event.name.toLowerCase().includes(term)
            && !(event.venue_address ?? "").toLowerCase().includes(term)) {
            return false;
        }

        const hosts = this.matchingHostIds();

        return hosts.size === 0 || hosts.has(String(event.organization_id ?? ""));
    }

    //  Picking a squad also shows events its garrison and club host; picking a club or
    //  garrison also shows events hosted by everything beneath it.
    matchingHostIds(): Set<string> {
        const ids = new Set<string>();

        for (const selected of this.active_hosting_ids) {
            let id: string | undefined = selected;

            while (id && !ids.has(id)) {
                ids.add(id);
                id = this.parents[id];
            }

            this.addDescendants(selected, ids);
        }

        return ids;
    }

    setSearch = (value: string) => {
        this.search_term = value;
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

    clearFilters = () => {
        this.search_term = "";
        this.clearHosting();
        this.clearOwnFilters();
    };

    protected clearOwnFilters(): void {}

    private addDescendants(id: string, ids: Set<string>): void {
        for (const child of this.children[id] ?? []) {
            ids.add(child);
            this.addDescendants(child, ids);
        }
    }

    private persistHosting(): void {
        setCookie(HOSTING_COOKIE, this.has_hosting_filter ? this.hosting_organization_ids.join(",") : "");
    }

    private restoreHosting(): void {
        const known = new Set(this.hosting_organizations.map((o) => String(o.id)));
        const hosting = getCookie(HOSTING_COOKIE) ?? getCookie("hosting_organization_id") ?? "";

        this.hosting_organization_ids = hosting.split(",")
            .map((id) => id.trim())
            .filter((id) => known.has(id));
    }
}
