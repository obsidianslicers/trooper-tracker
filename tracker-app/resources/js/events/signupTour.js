/**
 * The event page's "How sign-up works" tour (pages/events/inc/signup-tour.blade.php).
 *
 * Steps are `<template data-tour-step>` elements inside the component, in order:
 * - `data-title`: the card's heading; the template's content is its text.
 * - `data-target`: a selector for what the step points at. The step is left out when nothing
 *   visible matches, so steps for buttons a trooper can't see yet drop away by themselves.
 * - `data-unless`: leaves the step out when this selector matches something visible.
 *
 * The page dims around the target, which is highlighted in page coordinates so it scrolls with
 * the page instead of chasing it. Ending the tour (Done, Skip, close or Escape) remembers it
 * in localStorage under `storageKey`, and `autoStart` then no longer starts it. Starting it
 * from the button (the `signup-tour:start` event) always works.
 */
export default function signupTour({ autoStart = false, storageKey = 'tt.signupTourSeen' } = {}) {
    const visible = (el) => el !== null && el.getClientRects().length > 0;
    const find = (selector) => (selector
        ? [...document.querySelectorAll(selector)].find(visible) ?? null
        : null);
    const reducedMotion = () => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

    return {
        active: false,
        steps: [],
        index: 0,
        spot: null,
        layout: null,

        init() {
            if (autoStart && !this.seen()) {
                //  after the page settles, so the first target is where it'll stay
                setTimeout(() => this.start(), 600);
            }
        },

        seen() {
            try {
                return localStorage.getItem(storageKey) !== null;
            } catch {
                return false;
            }
        },

        get step() {
            return this.steps[this.index] ?? null;
        },

        get isLast() {
            return this.index === this.steps.length - 1;
        },

        //  page coordinates; with no target, a zero-size hole mid-screen just dims the page
        get spotStyle() {
            const s = this.spot ?? {
                top: window.scrollY + window.innerHeight / 2,
                left: window.scrollX + window.innerWidth / 2,
                width: 0,
                height: 0,
            };
            return `top:${s.top}px;left:${s.left}px;width:${s.width}px;height:${s.height}px`;
        },

        async start() {
            if (this.active) {
                return;
            }
            this.active = true;
            //  let the example rows show before looking for the steps' targets
            await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
            this.steps = this.collectSteps();
            this.show(0);
            this.$nextTick(() => this.$refs.next?.focus());
            //  content loading above the target moves it down the page
            if (typeof ResizeObserver !== 'undefined') {
                this.layout = new ResizeObserver(() => this.reposition());
                this.layout.observe(document.body);
            }
        },

        collectSteps() {
            return [...this.$el.querySelectorAll('template[data-tour-step]')]
                .map((t) => ({
                    title: t.dataset.title,
                    body: t.innerHTML,
                    target: t.dataset.target ?? null,
                    unless: t.dataset.unless ?? null,
                }))
                .filter((s) => s.target === null || find(s.target))
                .filter((s) => s.unless === null || !find(s.unless));
        },

        targetEl() {
            return this.step?.target ? find(this.step.target) : null;
        },

        show(i) {
            this.index = i;
            const el = this.targetEl();
            if (el) {
                const top = window.scrollY + el.getBoundingClientRect().top - Math.max(80, window.innerHeight * 0.2);
                window.scrollTo({ top: Math.max(0, top), behavior: reducedMotion() ? 'auto' : 'smooth' });
            }
            this.reposition();
        },

        //  in page coordinates, so scrolling never moves it; only a new step or a resize does
        reposition() {
            const el = this.targetEl();
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
        },

        next() {
            if (this.isLast) {
                this.end();
            } else {
                this.show(this.index + 1);
            }
        },

        back() {
            if (this.index > 0) {
                this.show(this.index - 1);
            }
        },

        end() {
            if (!this.active) {
                return;
            }
            this.active = false;
            this.spot = null;
            this.layout?.disconnect();
            this.layout = null;
            try {
                localStorage.setItem(storageKey, new Date().toISOString());
            } catch {
                //  private browsing: it starts again next time, which is harmless
            }
        },
    };
}
