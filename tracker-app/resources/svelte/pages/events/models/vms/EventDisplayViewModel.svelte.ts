import { router } from "@inertiajs/svelte";
import { tick } from "svelte";
import { SvelteSet } from "svelte/reactivity";
import type { DisplayShift } from "../display-types";

/** A question the page asks before something that can't simply be undone. */
export type Confirmation = {
    title: string;
    message: string;
    label: string;
    danger: boolean;
    run: () => void;
};

export type SendOptions = {
    shift_id?: number | null;
    only?: string[];
    files?: boolean;
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    /** Names the button that sent it, so that button can show it's working ({@link isBusy}). */
    busy?: string;
};

/** What a change on a shift reloads: the shifts, the summary at the top, and the tour's example. */
export const SHIFT_PROPS = ["shifts", "my_sign_ups", "tour", "toast", "flash", "errors"];

/** More troopers than this on a shift and Sign Up shows above the roster as well. */
export const LONG_ROSTER = 6;

export function trooperRowKey(id: number): string {
    return `trooper-${id}`;
}

export function guestRowKey(id: number): string {
    return `guest-${id}`;
}

/**
 * The event page: which shift cards are open, which roster rows have their Edit area open,
 * which shift is saving, and the confirmation in front of the page.
 *
 * Every change posts and reloads only the parts of the page it touched, so the open cards and
 * rows stay as they were.
 */
export class EventDisplayViewModel {
    readonly open_shifts = new SvelteSet<number>();
    readonly editing = new SvelteSet<string>();
    busy_shift: number | null = $state(null);
    sending = $state(false);
    confirmation: Confirmation | null = $state(null);
    sign_up_requested: number | null = $state(null);
    busy: string | null = $state(null);

    private readonly seen_rows = new Set<string>();
    private readonly seen_shifts = new Set<number>();
    private readonly mine = new Set<number>();

    constructor(shifts: DisplayShift[]) {
        this.sync(shifts);
    }

    /**
     * A card opens by itself when it's the only shift or the viewer is on it, and a row when it
     * still needs a costume; each only the first time it shows up, so what was closed stays closed.
     */
    sync(shifts: DisplayShift[]): void {
        for (const shift of shifts) {
            const is_new = !this.seen_shifts.has(shift.id);
            const just_joined = shift.is_mine && !this.mine.has(shift.id);

            if ((is_new && shifts.length === 1) || just_joined) {
                this.open_shifts.add(shift.id);
            }

            this.seen_shifts.add(shift.id);
            if (shift.is_mine) {
                this.mine.add(shift.id);
            } else {
                this.mine.delete(shift.id);
            }

            for (const row of [...shift.going, ...shift.stand_by]) {
                const key = trooperRowKey(row.id);

                if (!this.seen_rows.has(key) && row.opens_itself) {
                    this.editing.add(key);
                    this.open_shifts.add(shift.id);
                }

                this.seen_rows.add(key);
            }
        }
    }

    isOpen(shift_id: number): boolean {
        return this.open_shifts.has(shift_id);
    }

    toggleShift = (shift_id: number) => {
        if (this.open_shifts.has(shift_id)) {
            this.open_shifts.delete(shift_id);
        } else {
            this.open_shifts.add(shift_id);
        }
    };

    isEditing(key: string): boolean {
        return this.editing.has(key);
    }

    toggleEdit = (key: string) => {
        if (this.editing.has(key)) {
            this.editing.delete(key);
        } else {
            this.editing.add(key);
        }
    };

    /** "Sign up" at the top: opens the shifts that can be signed up for and goes to the roster. */
    goToSignUp = async (shift_ids: number[]) => {
        shift_ids.forEach((id) => this.open_shifts.add(id));
        await tick();
        document.getElementById("mission-roster")?.scrollIntoView({ behavior: "smooth" });
    };

    /** Sign Up above a long roster ({@link hasLongRoster}): ShiftSignUp goes to it and starts it. */
    requestSignUp = (shift_id: number) => {
        this.sign_up_requested = shift_id;
    };

    /** ShiftSignUp has picked up the request. */
    takeSignUpRequest(shift_id: number): boolean {
        if (this.sign_up_requested !== shift_id) {
            return false;
        }

        this.sign_up_requested = null;

        return true;
    }

    /** The button named `key` sent the request that's on its way. */
    isBusy(key: string): boolean {
        return this.sending && this.busy === key;
    }

    /** Long enough that its own Sign up, at the end, is out of sight. */
    hasLongRoster(shift: DisplayShift): boolean {
        return shift.going.length + shift.stand_by.length > LONG_ROSTER;
    }

    /** "Change" on the summary: opens that row's Edit area and goes to it. */
    editSignUp = async (row_id: number, shift_id: number) => {
        this.open_shifts.add(shift_id);
        this.editing.add(trooperRowKey(row_id));
        await tick();
        document.getElementById(`roster-row-${row_id}`)?.scrollIntoView({ behavior: "smooth", block: "center" });
    };

    send(url: string, data: Record<string, unknown>, options: SendOptions = {}): void {
        router.post(url, data as never, {
            preserveScroll: true,
            preserveState: true,
            only: options.only ?? SHIFT_PROPS,
            forceFormData: options.files ?? false,
            onStart: () => {
                this.sending = true;
                this.busy_shift = options.shift_id ?? null;
                this.busy = options.busy ?? null;
            },
            onFinish: () => {
                this.sending = false;
                this.busy_shift = null;
                this.busy = null;
            },
            onSuccess: () => options.onSuccess?.(),
            onError: (errors) => options.onError?.(errors as Record<string, string>),
        });
    }

    confirm(confirmation: Confirmation): void {
        this.confirmation = confirmation;
    }

    cancelConfirmation = () => {
        this.confirmation = null;
    };

    runConfirmation = () => {
        const confirmation = this.confirmation;
        this.confirmation = null;
        confirmation?.run();
    };
}
