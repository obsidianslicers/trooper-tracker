// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    SignUpTourViewModel,
    signUpTourSteps,
    type TourStep,
} from '../../../../../resources/svelte/pages/events/models/vms/SignUpTourViewModel.svelte';
import type { SignUpTourData } from '../../../../../resources/svelte/pages/events/models/display-types';

const KEY = 'tt.signupTourSeen.7';

const steps: TourStep[] = [
    { title: 'Intro', body: 'Hello', target: null },
    { title: 'Sign up', body: 'Press it', target: '.shift-signup .btn' },
    { title: 'Open a shift', body: 'Use the +', target: '#shift-container-1', unless: '.shift-signup .btn' },
    { title: 'Hidden', body: 'Never shown', target: '.gone' },
    { title: 'Missing', body: 'Never shown', target: '.missing' },
    { title: 'Done', body: 'Replay it', target: '[data-tour-replay]' },
];

const tour = (overrides: Partial<SignUpTourData> = {}): SignUpTourData => ({
    auto_start: true,
    storage_key: KEY,
    requires_costume: true,
    has_plans: true,
    plans: ['Going', 'Tentative'],
    going_label: 'Going',
    is_brief_pending: false,
    ...overrides,
});

describe('SignUpTourViewModel', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <button data-tour-replay>How sign-up works</button>
            <div class="shift-signup"><button class="btn">Sign Up</button></div>
            <div id="shift-container-1"></div>
            <div hidden><span class="gone">gone</span></div>`;
        //  jsdom lays nothing out; treat anything not inside [hidden] as on screen
        vi.spyOn(Element.prototype, 'getClientRects').mockImplementation(function (this: Element) {
            return (this.closest('[hidden]') ? [] : [{}]) as unknown as DOMRectList;
        });
        vi.stubGlobal('requestAnimationFrame', (cb: FrameRequestCallback) => cb(0));
        window.scrollTo = vi.fn() as typeof window.scrollTo;
        localStorage.clear();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.unstubAllGlobals();
        vi.useRealTimers();
    });

    it('starts by itself when asked to and not seen on this browser', () => {
        vi.useFakeTimers();
        const vm = new SignUpTourViewModel(() => steps, KEY);
        const start = vi.spyOn(vm, 'start').mockResolvedValue();

        vm.autoStart(true);
        vi.advanceTimersByTime(600);

        expect(start).toHaveBeenCalledOnce();
    });

    it('stays quiet once seen, or when not asked to start', () => {
        localStorage.setItem(KEY, '2026-11-16T00:00:00Z');
        expect(new SignUpTourViewModel(() => steps, KEY).autoStart(true)).toBeNull();

        localStorage.clear();
        expect(new SignUpTourViewModel(() => steps, KEY).autoStart(false)).toBeNull();
    });

    it('leaves out steps whose target is hidden or missing, or whose "unless" is on screen', async () => {
        const vm = new SignUpTourViewModel(() => steps, KEY);

        await vm.start();

        expect(vm.steps.map((s) => s.title)).toEqual(['Intro', 'Sign up', 'Done']);
        expect(vm.active).toBe(true);
        expect(vm.step?.title).toBe('Intro');
        expect(vm.spot).toBeNull();
    });

    it('walks forward and back, then remembers it was seen', async () => {
        const vm = new SignUpTourViewModel(() => steps, KEY);
        await vm.start();

        vm.back();
        expect(vm.index).toBe(0);

        vm.next();
        expect(vm.step?.title).toBe('Sign up');
        expect(vm.spot).not.toBeNull();
        expect(window.scrollTo).toHaveBeenCalled();

        vm.next();
        expect(vm.is_last).toBe(true);
        vm.next();

        expect(vm.active).toBe(false);
        expect(localStorage.getItem(KEY)).not.toBeNull();
    });

    it('replays from the button after it was seen', async () => {
        localStorage.setItem(KEY, '2026-11-16T00:00:00Z');
        const vm = new SignUpTourViewModel(() => steps, KEY);

        await vm.start();

        expect(vm.active).toBe(true);
    });

    it('copes without localStorage', async () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('blocked');
        });
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('blocked');
        });
        const vm = new SignUpTourViewModel(() => steps, KEY);

        expect(vm.seen()).toBe(false);
        await vm.start();
        expect(() => vm.end()).not.toThrow();
        expect(vm.active).toBe(false);
    });

    it('starts with the mission brief when it must be read first', () => {
        const titles = signUpTourSteps(tour({ is_brief_pending: true })).map((s) => s.title);

        expect(titles.slice(0, 3)).toEqual(['How signing up works', 'Read the mission brief first', 'Sign up for each shift']);
        expect(signUpTourSteps(tour()).map((s) => s.title)).not.toContain('Read the mission brief first');
    });

    it('says nothing about costumes on an event troopers don\'t wear one to', () => {
        const bodies = signUpTourSteps(tour({ requires_costume: false })).map((s) => s.body).join(' ');

        expect(bodies).not.toContain('costume (the one');
        expect(signUpTourSteps(tour()).map((s) => s.body).join(' ')).toContain('check your costume');
    });
});
