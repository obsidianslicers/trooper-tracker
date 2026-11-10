// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { destroy } = vi.hoisted(() => ({ destroy: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { delete: destroy } }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import CancelDeletion from '../../../../../resources/svelte/pages/account/components/CancelDeletion.svelte';

describe('CancelDeletion', () => {
    beforeEach(() => destroy.mockClear());

    it('shows when the account will be deleted', () => {
        render(CancelDeletion, { deletionDate: 'Jan 31, 2026' });

        expect(screen.getByText('Jan 31, 2026')).toBeTruthy();
    });

    it('cancels the deletion when the trooper asks', async () => {
        render(CancelDeletion, { deletionDate: 'Jan 31, 2026' });

        await fireEvent.click(screen.getByRole('button', { name: 'Cancel Deletion' }));

        expect(destroy).toHaveBeenCalledWith('/account.cancel-deletion', expect.any(Object));
    });

    it('ignores repeat clicks until the request finishes', async () => {
        render(CancelDeletion, { deletionDate: 'Jan 31, 2026' });

        await fireEvent.click(screen.getByRole('button', { name: 'Cancel Deletion' }));
        await fireEvent.click(screen.getByRole('button', { name: /Transmitting/ }));

        expect(destroy).toHaveBeenCalledTimes(1);
    });
});
