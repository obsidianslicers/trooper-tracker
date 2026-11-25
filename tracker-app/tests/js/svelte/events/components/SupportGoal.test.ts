// @vitest-environment jsdom
import { render, screen } from '@testing-library/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import SupportGoal from '../../../../../resources/svelte/pages/events/components/SupportGoal.svelte';
import type { SupportGoal as SupportGoalData } from '../../../../../resources/svelte/pages/events/models/types';

const support = (overrides: Partial<SupportGoalData> = {}): SupportGoalData => ({
    goal: 300,
    current: 75,
    progress: 25,
    message: 'Support us',
    url: 'https://example.test/give',
    ...overrides,
});

describe('SupportGoal', () => {
    beforeEach(() => {
        localStorage.clear();
        window.matchMedia = vi.fn().mockReturnValue({ matches: false }) as unknown as typeof window.matchMedia;
    });

    it('shows the goal and progress before it is reached', () => {
        render(SupportGoal, { support: support() });

        expect(screen.getByText("This Month's Support Goal", { exact: false })).toBeTruthy();
        expect(screen.getByText('25%')).toBeTruthy();
        expect(screen.getByText('❤️ Support the Garrison', { exact: false })).toBeTruthy();
        expect(screen.queryByText('Goal reached!')).toBeNull();
        expect(screen.queryByTestId('support-goal-confetti')).toBeNull();
    });

    it('turns into the goal reached card and keeps counting past 100', () => {
        render(SupportGoal, { support: support({ current: 336, progress: 112 }) });

        expect(screen.getByText('Goal reached!')).toBeTruthy();
        expect(screen.getByText('112%')).toBeTruthy();
        expect(screen.getByText('$336.00 raised')).toBeTruthy();
        expect(screen.getByText('❤️ Keep the momentum', { exact: false })).toBeTruthy();
        expect(screen.getByRole('progressbar').getAttribute('aria-valuetext')).toBe('112%');
    });

    it('celebrates only the first time it is seen this month', () => {
        const first = render(SupportGoal, { support: support({ current: 336, progress: 112 }) });
        expect(screen.getByTestId('support-goal-confetti')).toBeTruthy();
        first.unmount();

        render(SupportGoal, { support: support({ current: 336, progress: 112 }) });
        expect(screen.queryByTestId('support-goal-confetti')).toBeNull();
    });
});
