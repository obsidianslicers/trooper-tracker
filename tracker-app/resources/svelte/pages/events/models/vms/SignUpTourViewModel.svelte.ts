import { t } from "$lib/i18n";
import type { SignUpTourData } from "../display-types";

/**
 * One step: its heading and text (which may hold <b> and <i>), and what it points at. A step
 * whose target isn't on screen is left out, and so is one whose `unless` is.
 */
export type TourStep = {
    title: string;
    body: string;
    target: string | null;
    unless?: string | null;
};

type Spot = { top: number; left: number; width: number; height: number };

const visible = (el: Element | null): el is Element => el !== null && el.getClientRects().length > 0;

function find(selector: string | null | undefined): Element | null {
    return selector ? [...document.querySelectorAll(selector)].find(visible) ?? null : null;
}

function reducedMotion(): boolean {
    return window.matchMedia?.("(prefers-reduced-motion: reduce)").matches ?? false;
}

/** The tour's steps, in order. With no costume to pick (or no plans), those parts drop out. */
export function signUpTourSteps(tour: SignUpTourData): TourStep[] {
    const costumes = tour.requires_costume;
    const steps: TourStep[] = [{
        title: t("How signing up works"),
        body: (costumes
            ? t("Press <b>Sign Up</b>, check your costume and press <b>Confirm</b>. After that, any change saves the moment you pick it, and a message says so.")
            : t("Press <b>Sign Up</b>, then <b>Confirm</b>. After that, any change saves the moment you pick it, and a message says so."))
            + "<br /><br />" + t("This takes about a minute, and nothing in it signs you up."),
        target: null,
    }];

    if (tour.is_brief_pending) {
        steps.push({
            title: t("Read the mission brief first"),
            body: t("This event asks you to read the mission brief and confirm you've read it. The Sign Up buttons appear after you do."),
            target: ".mission-brief-container",
        });
    }

    steps.push(
        {
            title: t("Sign up for each shift"),
            body: costumes
                ? t("Every shift has its own <b>Sign Up</b>, after its list, because spots go in sign-up order. Press it, check your costume (the one you wore last time is already picked) and press <b>Confirm</b>. Not wearing one? Pick <i>Attending without a costume</i>. Working more than one shift? Sign up for each one.")
                : t("Every shift has its own <b>Sign Up</b>, after its list, because spots go in sign-up order. Press it, then <b>Confirm</b>. Working more than one shift? Sign up for each one."),
            target: ".shift-signup",
        },
        {
            title: t("Sign up for each shift"),
            body: costumes
                ? t("Every shift has its own <b>Sign Up</b>. Open a shift with its <b>+</b> to see it, press <b>Sign Up</b>, check your costume and press <b>Confirm</b>. Working more than one shift? Sign up for each one.")
                : t("Every shift has its own <b>Sign Up</b>. Open a shift with its <b>+</b> to see it, press <b>Sign Up</b>, then <b>Confirm</b>. Working more than one shift? Sign up for each one."),
            target: "[id^='shift-container-']",
            unless: ".shift-signup",
        },
        {
            title: t("Then it's your sign-up"),
            body: costumes
                ? t("Once you're signed up, your row in the list is your sign-up. Press <b>Edit</b> on it to change your costume or your plans, and <b>Done</b> when you're finished; each change saves as soon as you pick it.")
                : t("Once you're signed up, your row in the list is your sign-up. Press <b>Edit</b> on it to change your plans, and <b>Done</b> when you're finished."),
            target: "[data-tour-example='panel']",
        },
        {
            title: t("A backup costume, if you like"),
            body: t("Optional. Press <b>Add a backup costume</b> and pick one, in case your main costume can't make it. You can also add one while signing up."),
            target: "[data-tour-example='backup']",
        },
        {
            title: t("Change your plans here"),
            body: tour.has_plans
                ? t("Switch between Going and Tentative here. Can't make it after all? Press <b>Cancel my sign-up</b>.")
                : t("Can't make it after all? Press <b>Cancel my sign-up</b>."),
            target: "[data-tour-example='plans']",
        },
        {
            title: t("Bringing someone?"),
            body: t("Once you're going, and if the event allows it, these buttons add a friend who has a tracker account, or a guest who doesn't. They join the list with their own <b>Edit</b> button."),
            target: "[data-tour-example='extras']",
        },
        {
            title: t("Your sign-up at a glance"),
            body: t("Near the top of the event page you can see whether you're signed up, for every shift, without scrolling down to the roster."),
            target: ".my-signup-summary",
        },
        {
            title: t("After the troop"),
            body: t("Come back and press <b>Made It</b> or <b>Missed It</b> on your row."),
            target: "[data-tour-example='after']",
        },
        {
            title: t("That's it"),
            body: t("You can watch this again any time with this button."),
            target: "[data-tour-replay]",
        },
    );

    return steps;
}

/**
 * The event page's "How sign-up works" tour. The page dims around each step's target, which is
 * placed in page coordinates so it scrolls with the page instead of chasing it. Ending it (Done,
 * Skip, close or Escape) remembers it under `storage_key`, and starting by itself then stops;
 * the button above the roster always starts it.
 */
export class SignUpTourViewModel {
    active = $state(false);
    steps: TourStep[] = $state([]);
    index = $state(0);
    spot: Spot | null = $state(null);

    private layout: ResizeObserver | null = null;

    constructor(
        private readonly all_steps: () => TourStep[],
        private readonly storage_key: string,
    ) {}

    /** After the page settles, so the first target is where it'll stay. */
    autoStart(auto_start: boolean): ReturnType<typeof setTimeout> | null {
        return auto_start && !this.seen() ? setTimeout(() => void this.start(), 600) : null;
    }

    seen(): boolean {
        try {
            return localStorage.getItem(this.storage_key) !== null;
        } catch {
            return false;
        }
    }

    get step(): TourStep | null {
        return this.steps[this.index] ?? null;
    }

    get is_last(): boolean {
        return this.index === this.steps.length - 1;
    }

    get counter(): string {
        return t(":step of :count", { step: this.index + 1, count: this.steps.length });
    }

    /** With no target, a zero-size hole mid-screen just dims the page. */
    get spot_style(): string {
        const s = this.spot ?? {
            top: window.scrollY + window.innerHeight / 2,
            left: window.scrollX + window.innerWidth / 2,
            width: 0,
            height: 0,
        };

        return `top:${s.top}px;left:${s.left}px;width:${s.width}px;height:${s.height}px`;
    }

    start = async () => {
        if (this.active) {
            return;
        }

        this.active = true;
        //  let the example rows show before looking for the steps' targets
        await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
        this.steps = this.all_steps()
            .filter((s) => s.target === null || find(s.target))
            .filter((s) => !s.unless || !find(s.unless));
        this.show(0);

        if (typeof ResizeObserver !== "undefined") {
            this.layout = new ResizeObserver(() => this.reposition());
            this.layout.observe(document.body);
        }
    };

    next = () => {
        if (this.is_last) {
            this.end();
        } else {
            this.show(this.index + 1);
        }
    };

    back = () => {
        if (this.index > 0) {
            this.show(this.index - 1);
        }
    };

    end = () => {
        if (!this.active) {
            return;
        }

        this.active = false;
        this.spot = null;
        this.layout?.disconnect();
        this.layout = null;

        try {
            localStorage.setItem(this.storage_key, new Date().toISOString());
        } catch {
            //  private browsing: it starts again next time, which is harmless
        }
    };

    private show(index: number): void {
        this.index = index;
        const el = find(this.step?.target);

        if (el) {
            const top = window.scrollY + el.getBoundingClientRect().top - Math.max(80, window.innerHeight * 0.2);
            window.scrollTo({ top: Math.max(0, top), behavior: reducedMotion() ? "auto" : "smooth" });
        }

        this.reposition();
    }

    private reposition(): void {
        const el = find(this.step?.target);

        if (!el) {
            this.spot = null;
            return;
        }

        const r = el.getBoundingClientRect();
        const pad = 6;

        this.spot = {
            top: r.top + window.scrollY - pad,
            left: r.left + window.scrollX - pad,
            width: r.width + pad * 2,
            height: r.height + pad * 2,
        };
    }
}
