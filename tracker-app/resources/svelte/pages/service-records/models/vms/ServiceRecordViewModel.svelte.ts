import type { RecordTrooper } from "../record-types";

export type RecordTab = "upcoming" | "history" | "awards" | "costumes" | "photos" | "support" | "credit-requests";

export type ShiftsLayout = "cards" | "table";

const LAYOUT_KEY = "upcomingShiftsView";

function readLayout(): ShiftsLayout | null {
    try {
        const saved = localStorage.getItem(LAYOUT_KEY);
        return saved === "cards" || saved === "table" ? saved : null;
    } catch {
        return null;
    }
}

/**
 * The service record page: which tab is open, and cards or table for the shift lists
 * (remembered in the browser under the key the Blade page used).
 */
export class ServiceRecordViewModel {
    tab: RecordTab = $state("upcoming");
    layout: ShiftsLayout = $state("cards");

    constructor(trooper: RecordTrooper, width: number = 1280) {
        this.tab = trooper.is_departed ? "history" : "upcoming";
        // phones get cards; wider screens a table, until the trooper picks
        this.layout = readLayout() ?? (width < 992 ? "cards" : "table");
    }

    toggleLayout = () => {
        this.layout = this.layout === "cards" ? "table" : "cards";

        try {
            localStorage.setItem(LAYOUT_KEY, this.layout);
        } catch {
            // private windows can refuse; the choice just isn't remembered
        }
    };
}
