import { t } from "$lib/i18n";
import { AutosaveViewModel } from "$lib/domains/autosave.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { EventShift, EventStation, UpdateEventPageData } from "../types";

export type StationForm = {
    /** The station's id, or a negative number for one not saved yet. */
    key: number;
    name: string;
    troopers_allowed: string;
};

export type ShiftForm = {
    date: string;
    starts_at: string;
    ends_at: string;
    status: string | null;
    stations: StationForm[];
};

let next_draft_key = -1;

/** A key for a row that isn't saved yet: negative, so the server creates it. */
export function draftKey(): number {
    return next_draft_key--;
}

function toStationForm(station: EventStation): StationForm {
    return {
        key: station.id,
        name: station.name,
        troopers_allowed: String(station.troopers_allowed ?? ""),
    };
}

function isComplete(station: StationForm): boolean {
    return station.name.trim() !== "" && station.troopers_allowed.trim() !== "";
}

/**
 * One shift on the Shifts tab: its date, times, status and stations, saved as they change.
 * A station added here is saved once it has both a name and a capacity.
 */
export class ShiftViewModel extends AutosaveViewModel<ShiftForm, UpdateEventPageData> {
    pending_removal: StationForm | null = $state(null);

    constructor(
        public readonly event_id: number,
        public readonly shift_id: number,
        shift: Pick<EventShift, "date" | "starts_at" | "ends_at"> & Partial<EventShift>,
        private readonly onsaved: (() => void) | null = null,
    ) {
        super({
            date: shift.date,
            starts_at: shift.starts_at,
            ends_at: shift.ends_at,
            status: shift.status ?? null,
            stations: (shift.stations ?? []).map(toStationForm),
        });
    }

    get is_complete(): boolean {
        return this.form.date !== "" && this.form.starts_at !== "" && this.form.ends_at !== "";
    }

    get confirm_message(): string {
        return t("Remove station :station?", { station: this.pending_removal?.name ?? "" });
    }

    error(field: string): string | undefined {
        return this.errors[`shifts.${this.shift_id}.${field}`];
    }

    stationError(station: StationForm): string | undefined {
        return this.error(`stations.${station.key}.name`)
            ?? this.error(`stations.${station.key}.troopers_allowed`);
    }

    /** Saves once the shift has its date and both times. */
    change = () => {
        if (this.is_complete) {
            this.save();
        }
    };

    addStation = () => {
        this.form.stations = [...this.form.stations, { key: draftKey(), name: "", troopers_allowed: "" }];
    };

    changeStation = (station: StationForm) => {
        if (station.key > 0 || isComplete(station)) {
            this.change();
        }
    };

    removeStation = (station: StationForm) => {
        if (station.key < 0) {
            this.form.stations = this.form.stations.filter((s) => s.key !== station.key);
            return;
        }

        this.pending_removal = station;
    };

    cancelRemoval = () => {
        this.pending_removal = null;
    };

    confirmRemoval = () => {
        const station = this.pending_removal;
        this.pending_removal = null;

        if (station === null) {
            return;
        }

        router.post(
            getRoute("admin.events.shifts.stations.remove", {
                event: this.event_id,
                event_shift: this.shift_id,
                event_shift_station: station.key,
            }),
            {},
            createPartialReloadOptions({
                async: true,
                onSuccess: (page) => this.synced(page.props.results as UpdateEventPageData),
            }),
        );
    };

    moveStation = (from: number, to: number) => {
        const stations = [...this.form.stations];
        const [moved] = stations.splice(from, 1);

        stations.splice(to, 0, moved);
        this.form.stations = stations;

        const ordered_ids = stations.filter((s) => s.key > 0).map((s) => s.key);

        router.post(
            getRoute("admin.events.shifts.stations.reorder", {
                event: this.event_id,
                event_shift: this.shift_id,
            }),
            { ids: ordered_ids },
            createPartialReloadOptions({ async: true }),
        );
    };

    protected url(): string {
        return getRoute("admin.events.shifts", { event: this.event_id });
    }

    protected payload(data: ShiftForm): Record<string, unknown> {
        const stations = data.stations
            .filter((station) => station.key > 0 || isComplete(station))
            .map(({ key, name, troopers_allowed }) => [key, { name, troopers_allowed }]);
        const shift: Record<string, unknown> = {
            date: data.date,
            starts_at: data.starts_at,
            ends_at: data.ends_at,
            stations: Object.fromEntries(stations),
        };

        if (data.status) {
            shift.status = data.status;
        }

        return { shifts: { [this.shift_id]: shift } };
    }

    /** Saved stations get their ids from the server; unfinished new ones stay. */
    protected synced(results: UpdateEventPageData | undefined): void {
        if (this.shift_id < 0) {
            this.onsaved?.();
            return;
        }

        const shift = results?.shifts.find((s) => s.id === this.shift_id);

        if (!shift) {
            return;
        }

        const unfinished = this.form.stations.filter((s) => s.key < 0 && !isComplete(s));
        const stations = [...shift.stations.map(toStationForm), ...unfinished];

        this.form.defaults({ ...this.form.data(), stations });
        this.form.stations = stations;
    }
}
