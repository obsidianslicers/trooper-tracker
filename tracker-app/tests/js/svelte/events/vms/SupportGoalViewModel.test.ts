// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { SupportGoalViewModel } from '../../../../../resources/svelte/pages/events/models/vms/SupportGoalViewModel.svelte';
import type { SupportGoal } from '../../../../../resources/svelte/pages/events/models/types';

const NOV = () => new Date(2026, 10, 25);

const support = (overrides: Partial<SupportGoal> = {}): SupportGoal => ({
    goal: 300,
    current: 336,
    progress: 112,
    message: 'Support us',
    url: null,
    ...overrides,
});

const motion = (reduce: boolean) => {
    window.matchMedia = vi.fn().mockReturnValue({ matches: reduce }) as unknown as typeof window.matchMedia;
};

describe('SupportGoalViewModel', () => {
    beforeEach(() => {
        localStorage.clear();
        motion(false);
    });

    afterEach(() => vi.restoreAllMocks());

    it('keeps counting past 100 but fills the bar only to 100', () => {
        const subject = new SupportGoalViewModel(support(), NOV);

        expect(subject.is_reached).toBe(true);
        expect(subject.percent).toBe(112);
        expect(subject.bar_width).toBe(100);
    });

    it('is not reached below the goal', () => {
        const subject = new SupportGoalViewModel(support({ current: 75, progress: 25 }), NOV);

        expect(subject.is_reached).toBe(false);
        expect(subject.bar_width).toBe(25);
        expect(subject.celebrate()).toBe(false);
    });

    it('celebrates once a month per browser', () => {
        expect(new SupportGoalViewModel(support(), NOV).celebrate()).toBe(true);
        expect(new SupportGoalViewModel(support(), NOV).celebrate()).toBe(false);
        expect(localStorage.getItem('tt.supportGoalCelebrated.2026-11')).toBe('1');

        const december = () => new Date(2026, 11, 2);
        expect(new SupportGoalViewModel(support(), december).celebrate()).toBe(true);
    });

    it('skips the celebration for people who asked for less motion', () => {
        motion(true);

        expect(new SupportGoalViewModel(support(), NOV).celebrate()).toBe(false);
        expect(localStorage.length).toBe(0);
    });

    it('skips the celebration when storage is unavailable', () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('blocked');
        });

        expect(new SupportGoalViewModel(support(), NOV).celebrate()).toBe(false);
    });
});
