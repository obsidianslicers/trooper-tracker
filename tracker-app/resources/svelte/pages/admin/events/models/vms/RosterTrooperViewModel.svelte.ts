import { AutosaveViewModel } from "$lib/domains/autosave.svelte";
import { getRoute } from "$lib/utils";
import type { CreditOption, RosterTrooper, EventPageResults } from "../types";

export type RosterTrooperForm = {
    costume_id: number | null;
    status: string;
    event_shift_station_id: number | null;
    organization_ids: number[];
};

function toForm(row: RosterTrooper): RosterTrooperForm {
    return {
        costume_id: row.costume_id,
        status: row.status,
        event_shift_station_id: row.event_shift_station_id,
        organization_ids: [...row.organization_ids],
    };
}

/**
 * One trooper on the Roster tab: costume, status, station and credited clubs, saved
 * as they change. The server can move a Going trooper to Stand By when the shift or
 * station is full, so each save reloads the row from the fresh roster.
 */
export class RosterTrooperViewModel extends AutosaveViewModel<RosterTrooperForm, EventPageResults> {
    org_options: CreditOption[] = $state([]);

    constructor(
        public readonly event_id: number,
        public readonly row: RosterTrooper,
    ) {
        super(toForm(row));
        this.org_options = row.org_options;
    }

    isCredited(organization_id: number): boolean {
        return this.form.organization_ids.includes(organization_id);
    }

    toggleCredit = (organization_id: number) => {
        this.form.organization_ids = this.isCredited(organization_id)
            ? this.form.organization_ids.filter((id) => id !== organization_id)
            : [...this.form.organization_ids, organization_id];
        this.save();
    };

    /** A new costume changes which clubs can be credited. */
    changeCostume = async () => {
        const url = getRoute("admin.events.troopers.org-options", {
            event: this.event_id,
            event_trooper: this.row.id,
        });
        const query = new URLSearchParams({ costume_id: String(this.form.costume_id ?? "") });

        try {
            const response = await fetch(`${url}?${query}`, { headers: { Accept: "application/json" } });
            const choices = await response.json();

            this.org_options = choices.org_options;
            this.form.organization_ids = choices.organization_ids;
        } catch {
            // keep the current choices; the save below still records the costume
        }

        this.save();
    };

    protected url(): string {
        return getRoute("admin.events.troopers.update", { event: this.event_id });
    }

    protected payload(data: RosterTrooperForm): Record<string, unknown> {
        return {
            roster_complete: 1,
            troopers: { [this.row.id]: { ...data, organization_selection: 1 } },
        };
    }

    protected synced(results: EventPageResults | undefined): void {
        const row = results?.roster?.shifts
            .flatMap((shift) => shift.troopers)
            .find((trooper) => trooper.id === this.row.id);

        if (!row || this.form.processing) {
            return;
        }

        this.org_options = row.org_options;
        this.form.defaults(toForm(row)).reset();
    }
}
