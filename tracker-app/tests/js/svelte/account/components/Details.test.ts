// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { form } = vi.hoisted(() => ({
    form: {
        legal_name: 'Anakin Skywalker',
        display_name: 'Vader',
        phone: '',
        display_costume_id: null as number | null,
        theme: 'stormtrooper',
        post: vi.fn(),
        defaults: vi.fn(),
        processing: false,
        isDirty: false,
        errors: {} as Record<string, string[]>,
    },
}));

vi.mock('@inertiajs/svelte', () => ({ useForm: () => form, router: { post: vi.fn() } }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import Details from '../../../../../resources/svelte/pages/account/components/Details.svelte';

const details = (display_costumes: { value: number; label: string }[] = []) => ({
    legal_name: 'Anakin Skywalker',
    display_name: 'Vader',
    phone: '',
    display_costume_id: null,
    theme: 'stormtrooper',
    display_costumes,
    theme_enums: [
        { value: 'stormtrooper', label: 'Stormtrooper' },
        { value: 'mandalorian', label: 'Mandalorian' },
    ],
});

describe('Details', () => {
    beforeEach(() => {
        form.post.mockClear();
        form.isDirty = false;
        form.errors = {};
        document.body.className = '';
    });

    it('shows the trooper profile', () => {
        render(Details, { details: details() });

        expect((screen.getByLabelText('Legal Name') as HTMLInputElement).value).toBe(
            'Anakin Skywalker',
        );
        expect((screen.getByLabelText('Display Name') as HTMLInputElement).value).toBe('Vader');
    });

    it('only offers a display format when the trooper has display costumes', () => {
        const { unmount } = render(Details, { details: details() });
        expect(screen.queryByLabelText('Preferred 501st Display Format')).toBeNull();
        unmount();

        render(Details, { details: details([{ value: 5, label: 'TK 1' }]) });
        expect(screen.getByLabelText('Preferred 501st Display Format')).toBeTruthy();
    });

    it('cannot be saved until something changes', () => {
        render(Details, { details: details() });

        expect((screen.getByRole('button', { name: 'Save' }) as HTMLButtonElement).disabled).toBe(
            true,
        );
    });

    it('saves the profile', async () => {
        form.isDirty = true;
        render(Details, { details: details() });

        await fireEvent.click(screen.getByRole('button', { name: 'Save' }));

        expect(form.post).toHaveBeenCalledWith('/account.update-profile', expect.any(Object));
    });

    it('shows validation errors', () => {
        form.errors = { legal_name: ['The legal name field is required.'] };

        render(Details, { details: details() });

        expect(screen.getByText('The legal name field is required.')).toBeTruthy();
    });

    it('applies the theme to the page', () => {
        render(Details, { details: details() });

        expect(document.body.classList.contains('theme-stormtrooper')).toBe(true);
    });
});
