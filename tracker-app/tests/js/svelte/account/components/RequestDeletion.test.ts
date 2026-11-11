// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post } = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { post }, useForm: vi.fn() }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import RequestDeletion from '../../../../../resources/svelte/pages/account/components/RequestDeletion.svelte';

describe('RequestDeletion', () => {
    beforeEach(() => post.mockClear());

    it('warns about the danger zone', () => {
        render(RequestDeletion);

        expect(screen.getByText('Danger Zone')).toBeTruthy();
    });

    it('requires typing DELETE before the request can be sent', async () => {
        render(RequestDeletion);

        const confirm = screen.getByLabelText('Type DELETE to confirm') as HTMLInputElement;
        const submit = screen.getByRole('button', { name: 'Delete', hidden: true }) as HTMLButtonElement;
        expect(submit.disabled).toBe(true);

        await fireEvent.input(confirm, { target: { value: 'DELETE' } });

        expect(submit.disabled).toBe(false);
    });

    it('sends the deletion request once confirmed', async () => {
        render(RequestDeletion);

        await fireEvent.input(screen.getByLabelText('Type DELETE to confirm'), {
            target: { value: 'DELETE' },
        });
        await fireEvent.click(screen.getByRole('button', { name: 'Delete', hidden: true }));

        expect(post).toHaveBeenCalledWith('/account.request-deletion', {}, expect.any(Object));
    });
});
