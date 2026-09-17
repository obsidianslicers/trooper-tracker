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
    all_org_options: OrgOption[] | null;
};

export type FilteredTrooper = {
    id: number;
    display_name: string;
};

export type MissingCreditsPageData = {
    rows: MissingCreditRow[];
    is_administrator: boolean;
    filtered_trooper: FilteredTrooper | null;
};

export class MissingCreditsViewModel extends ViewModel {
    rows: MissingCreditRow[] = $state([]);
    is_administrator: boolean = $state(false);
    filtered_trooper: FilteredTrooper | null = $state(null);
    assigning_id: number | null = $state(null);
    selected: Record<number, number[]> = $state({});

    constructor(public pageData: MissingCreditsPageData) {
        super();
        this.rows = pageData.rows;
        this.is_administrator = pageData.is_administrator;
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
        return row.org_options.length > 0 ? row.org_options : (row.all_org_options ?? []);
    };

    isManualOverride = (row: MissingCreditRow): boolean => {
        return row.org_options.length === 0 && (row.all_org_options?.length ?? 0) > 0;
    };

    isSelected = (row: MissingCreditRow, org_id: number): boolean => {
        return (this.selected[row.event_trooper_id] ?? []).includes(org_id);
    };

    toggleOrg = (row: MissingCreditRow, org_id: number, checked: boolean) => {
        const current = this.selected[row.event_trooper_id] ?? [];

        this.selected[row.event_trooper_id] = checked
            ? [...current, org_id]
            : current.filter((id) => id !== org_id);
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
            },
            onFinish: () => {
                this.assigning_id = null;
            },
        });

        router.post(url, { organization_ids }, options);
    };
}
