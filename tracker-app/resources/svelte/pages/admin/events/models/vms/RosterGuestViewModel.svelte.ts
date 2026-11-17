import { AutosaveViewModel } from "$lib/domains/autosave.svelte";
import { getRoute } from "$lib/utils";
import type { RosterGuest } from "../types";

export type RosterGuestForm = {
    status: string;
};

/**
 * One guest on the Roster tab, whose status saves as it changes.
 */
export class RosterGuestViewModel extends AutosaveViewModel<RosterGuestForm> {
    constructor(
        public readonly event_id: number,
        public readonly guest: RosterGuest,
    ) {
        super({ status: guest.status });
    }

    protected url(): string {
        return getRoute("admin.events.troopers", { event: this.event_id });
    }

    protected payload(data: RosterGuestForm): Record<string, unknown> {
        return { roster_complete: 1, guests: { [this.guest.id]: data } };
    }
}
