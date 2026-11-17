// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import signupTour from '../../../resources/js/events/signupTour.js';

const KEY = 'tt.signupTourSeen.7';

//  the component as Alpine would run it: its element, refs and nextTick
function mount(options = {}) {
    const tour = signupTour({ storageKey: KEY, ...options });
    tour.$el = document.querySelector('[data-signup-tour]');
    tour.$refs = {};
    tour.$nextTick = (fn) => fn();
    return tour;
}

describe('signupTour', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <button data-tour-replay>How sign-up works</button>
            <div class="shift-signup-primary"><button class="btn">Sign Up</button></div>
            <div id="hidden-target" hidden><span class="gone">gone</span></div>
            <div data-signup-tour>
                <template data-tour-step data-title="Intro">Hello</template>
                <template data-tour-step data-target=".shift-signup-primary .btn" data-title="Sign up">Press it</template>
                <template data-tour-step data-target="#shift-container-1" data-unless=".shift-signup-primary .btn" data-title="Open a shift">Use the +</template>
                <template data-tour-step data-target=".gone" data-title="Hidden">Never shown</template>
                <template data-tour-step data-target=".missing" data-title="Missing">Never shown</template>
                <template data-tour-step data-target="[data-tour-replay]" data-title="Done">Replay it</template>
            </div>`;
        //  jsdom lays nothing out; treat anything not inside [hidden] as on screen
        vi.spyOn(Element.prototype, 'getClientRects').mockImplementation(function () {
            return this.closest('[hidden]') ? [] : [{}];
        });
        vi.stubGlobal('requestAnimationFrame', (cb) => cb());
        window.scrollTo = vi.fn();
        localStorage.clear();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.unstubAllGlobals();
        vi.useRealTimers();
    });

    it('starts by itself when asked to and not seen on this browser', () => {
        vi.useFakeTimers();
        const tour = mount({ autoStart: true });
        tour.start = vi.fn();

        tour.init();
        vi.advanceTimersByTime(600);

        expect(tour.start).toHaveBeenCalledOnce();
    });

    it('stays quiet once seen, or when not asked to start', () => {
        vi.useFakeTimers();
        localStorage.setItem(KEY, '2026-11-16T00:00:00Z');
        const seen = mount({ autoStart: true });
        seen.start = vi.fn();
        seen.init();

        localStorage.clear();
        const notAsked = mount({ autoStart: false });
        notAsked.start = vi.fn();
        notAsked.init();

        vi.advanceTimersByTime(5000);

        expect(seen.start).not.toHaveBeenCalled();
        expect(notAsked.start).not.toHaveBeenCalled();
    });

    it('leaves out steps whose target is hidden or missing, or whose "unless" is on screen', async () => {
        const tour = mount();

        await tour.start();

        expect(tour.steps.map((s) => s.title)).toEqual(['Intro', 'Sign up', 'Done']);
        expect(tour.active).toBe(true);
        expect(tour.step.title).toBe('Intro');
        expect(tour.spot).toBeNull();
    });

    it('walks forward and back, then remembers it was seen', async () => {
        const tour = mount();
        await tour.start();

        tour.back();
        expect(tour.index).toBe(0);

        tour.next();
        expect(tour.step.title).toBe('Sign up');
        expect(tour.spot).not.toBeNull();
        expect(window.scrollTo).toHaveBeenCalled();

        tour.next();
        expect(tour.isLast).toBe(true);
        tour.next();

        expect(tour.active).toBe(false);
        expect(localStorage.getItem(KEY)).not.toBeNull();
    });

    it('replays from the button after it was seen', async () => {
        localStorage.setItem(KEY, '2026-11-16T00:00:00Z');
        const tour = mount({ autoStart: true });

        await tour.start();

        expect(tour.active).toBe(true);
    });

    it('copes without localStorage', async () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('blocked');
        });
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('blocked');
        });
        const tour = mount();

        expect(tour.seen()).toBe(false);
        await tour.start();
        expect(() => tour.end()).not.toThrow();
        expect(tour.active).toBe(false);
    });
});
