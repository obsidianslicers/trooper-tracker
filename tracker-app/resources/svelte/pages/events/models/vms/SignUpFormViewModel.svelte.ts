import type { PickOption, SignUpChoices } from "../display-types";

/**
 * The choices made before Confirm, for your own Sign Up or a friend's: club, station, costume,
 * backup costume and plans. Confirm waits for a costume (or "Attending without a costume"),
 * and for a station when a friend is signed up to a shift that uses them. A friend's club
 * starts on their first one.
 */
export class SignUpFormViewModel {
    is_open = $state(false);
    organization_id = $state("");
    station_id = $state("");
    costume_id = $state("");
    backup_costume_id = $state("");
    has_backup = $state(false);
    status = $state("");

    constructor(
        public readonly choices: SignUpChoices & { plans?: PickOption[] },
        public readonly for_friend: boolean = false,
    ) {
        this.costume_id = choices.default_costume;
        this.status = String(choices.plans?.[0]?.value ?? "");

        if (choices.organizations.length === 1 || (for_friend && choices.organizations.length > 0)) {
            this.organization_id = String(choices.organizations[0].value);
        }
    }

    /** Whether there's anything to choose; with nothing, Sign Up signs up straight away. */
    get has_choices(): boolean {
        return this.choices.requires_costume
            || (this.choices.plans?.length ?? 0) > 1
            || this.choices.organizations.length > 1
            || this.choices.stations.length > 0;
    }

    get only_organization(): PickOption | null {
        return this.choices.organizations.length === 1 ? this.choices.organizations[0] : null;
    }

    get can_confirm(): boolean {
        const needs_station = this.for_friend && this.choices.stations.length > 0;

        return this.costume_id !== "" && (!needs_station || this.station_id !== "");
    }

    open = () => {
        this.is_open = true;
    };

    close = () => {
        this.is_open = false;
    };

    addBackup = () => {
        this.has_backup = true;
    };

    payload(extra: Record<string, unknown> = {}): Record<string, unknown> {
        const data: Record<string, unknown> = { ...extra };

        if (this.choices.requires_costume) {
            data.costume_id = this.costume_id;
        }

        if (this.organization_id !== "") {
            data.organization_id = this.organization_id;
        }

        if (this.station_id !== "") {
            data.event_shift_station_id = this.station_id;
        }

        if (this.has_backup && this.backup_costume_id !== "") {
            data.backup_costume_id = this.backup_costume_id;
        }

        if (this.status !== "" && (this.choices.plans?.length ?? 0) > 1) {
            data.status = this.status;
        }

        return data;
    }
}
