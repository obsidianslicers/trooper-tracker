import { AutosaveViewModel } from "$lib/domains/autosave.svelte";
import { getRoute } from "$lib/utils";
import type { EventShiftCharity } from "../types";

export type CharityForm = {
    charity_name: string;
    charity_hours: string;
    charity_direct_funds: string;
    charity_indirect_funds: string;
    charity_notes: string;
};

/**
 * One shift's charity numbers on the Charity tab, saved as they change.
 */
export class CharityViewModel extends AutosaveViewModel<CharityForm> {
    constructor(
        public readonly event_id: number,
        public readonly shift: EventShiftCharity,
    ) {
        super({
            charity_name: shift.charity_name,
            charity_hours: shift.charity_hours,
            charity_direct_funds: shift.charity_direct_funds,
            charity_indirect_funds: shift.charity_indirect_funds,
            charity_notes: shift.charity_notes,
        });
    }

    error(field: keyof CharityForm): string | undefined {
        return this.errors[`shifts.${this.shift.id}.${field}`];
    }

    protected url(): string {
        return getRoute("admin.events.charity.update", { event: this.event_id });
    }

    protected payload(data: CharityForm): Record<string, unknown> {
        return { shifts: { [this.shift.id]: data } };
    }
}
