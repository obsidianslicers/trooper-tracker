import { ViewModel } from "$lib/domains/types.svelte";
import toastStateSvelte from "$lib/states/toast-state.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";

export type OrgOption = {
    id: number;
    name: string;
};

export type MissingCreditRow = {
    event_trooper_id: number;
    trooper_id: number;
    trooper_name: string;
    event_id: number;
    event_name: string;
    shift_label: string;
    shift_starts_at: string | null;
    costume_name: string | null;
    org_options: OrgOption[];
    has_eligible_options: boolean;
    has_orphaned_db_value: boolean;
    fallback_org_options: OrgOption[];
    trooper_has_no_club_membership: boolean;
};

export type FilteredTrooper = {
    id: number;
    display_name: string;
};

export type MissingCreditsPageData = {
    rows: MissingCreditRow[];
    total: number;
    next_offset: number;
    has_more: boolean;
    filtered_trooper: FilteredTrooper | null;
};

export class MissingCreditsViewModel extends ViewModel {
    rows: MissingCreditRow[] = $state([]);
    total: number = $state(0);
    next_offset: number = $state(0);
    has_more: boolean = $state(false);
    filtered_trooper: FilteredTrooper | null = $state(null);
    assigning_id: number | null = $state(null);
    loading_more: boolean = $state(false);
    selected: Record<number, number[]> = $state({});

    constructor(public pageData: MissingCreditsPageData) {
        super();
        this.rows = pageData.rows;
        this.total = pageData.total;
        this.next_offset = pageData.next_offset;
        this.has_more = pageData.has_more;
        this.filtered_trooper = pageData.filtered_trooper;

        this.rows.forEach((row) => {
            this.selected[row.event_trooper_id] = [];
        });
    }

    get clear_filter_route(): string {
        return getRoute("admin.service-records.missing-credits");
    }

    trooperServiceRecordRoute = (trooper_id: number): string => {
        return getRoute("service-records.trooper", { trooper: trooper_id });
    };

    optionsFor = (row: MissingCreditRow): OrgOption[] => {
        return row.org_options.length > 0 ? row.org_options : row.fallback_org_options;
    };

    isFallback = (row: MissingCreditRow): boolean => {
        return row.org_options.length === 0 && row.fallback_org_options.length > 0;
    };

    isSelected = (row: MissingCreditRow, org_id: number): boolean => {
        return (this.selected[row.event_trooper_id] ?? []).includes(org_id);
    };

    toggleOrg = (row: MissingCreditRow, org_id: number) => {
        const current = this.selected[row.event_trooper_id] ?? [];

        this.selected[row.event_trooper_id] = current.includes(org_id)
            ? current.filter((id) => id !== org_id)
            : [...current, org_id];
    };

    assign = (row: MissingCreditRow) => {
        const organization_ids = this.selected[row.event_trooper_id] ?? [];

        if (organization_ids.length === 0) {
            toastStateSvelte.danger("Select at least one organization first.");
            return;
        }

        this.assigning_id = row.event_trooper_id;

        const url = getRoute("admin.service-records.missing-credits.assign", {
            event_trooper: row.event_trooper_id,
        });

        const options = createPartialReloadOptions({
            only: ["flash"],
            onSuccess: () => {
                toastStateSvelte.success(`Credit assigned for ${row.trooper_name}.`);
                this.rows = this.rows.filter((r) => r.event_trooper_id !== row.event_trooper_id);
                this.total = Math.max(0, this.total - 1);
            },
            onError: (errors) => {
                const message =
                    Object.values(errors)[0] ?? "Couldn't assign credit — please try again.";
                toastStateSvelte.danger(Array.isArray(message) ? message[0] : message);
            },
            onFinish: () => {
                this.assigning_id = null;
            },
        });

        router.post(url, { organization_ids, is_override: this.isFallback(row) }, options);
    };

    loadMore = () => {
        if (this.loading_more || !this.has_more) {
            return;
        }

        this.loading_more = true;

        const url = getRoute("admin.service-records.missing-credits");
        const data: Record<string, number> = { offset: this.next_offset };

        if (this.filtered_trooper) {
            data.trooper_id = this.filtered_trooper.id;
        }

        const options = createPartialReloadOptions({
            only: ["rows", "total", "next_offset", "has_more"],
            onSuccess: (page) => {
                const props = page.props as unknown as MissingCreditsPageData;

                props.rows.forEach((row) => {
                    this.selected[row.event_trooper_id] = [];
                });

                this.rows = [...this.rows, ...props.rows];
                this.total = props.total;
                this.next_offset = props.next_offset;
                this.has_more = props.has_more;
            },
            onError: () => {
                toastStateSvelte.danger("Couldn't load more — please try again.");
            },
            onFinish: () => {
                this.loading_more = false;
            },
        });

        router.get(url, data, options);
    };
}
