import type { SupportGoal } from "../types";

const STORAGE_PREFIX = "tt.supportGoalCelebrated.";

const money = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function reducedMotion(): boolean {
    return window.matchMedia?.("(prefers-reduced-motion: reduce)").matches ?? false;
}

/**
 * The support goal card: how far along the month is, and once the goal is reached, whether this
 * browser has had its one celebration for the month yet.
 */
export class SupportGoalViewModel {
    constructor(
        private readonly support: SupportGoal,
        private readonly now: () => Date = () => new Date(),
    ) {}

    /** Keeps counting past 100, so extra giving still shows. */
    get percent(): number {
        return Math.max(0, Math.round(this.support.progress));
    }

    get bar_width(): number {
        return Math.min(100, this.percent);
    }

    get is_reached(): boolean {
        return this.support.goal > 0 && this.support.progress >= 100;
    }

    get goal(): string {
        return money.format(this.support.goal);
    }

    get raised(): string {
        return money.format(this.support.current);
    }

    get storage_key(): string {
        const today = this.now();
        const month = String(today.getMonth() + 1).padStart(2, "0");

        return `${STORAGE_PREFIX}${today.getFullYear()}-${month}`;
    }

    /** True once a month per browser, and never for people who asked for less motion. */
    celebrate(): boolean {
        if (!this.is_reached || reducedMotion()) {
            return false;
        }

        try {
            if (localStorage.getItem(this.storage_key) !== null) {
                return false;
            }
            localStorage.setItem(this.storage_key, "1");
        } catch {
            return false;
        }

        return true;
    }
}
