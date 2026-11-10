import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post, defaults, useForm } = vi.hoisted(() => {
    const post = vi.fn();
    const defaults = vi.fn();
    const useForm = vi.fn((data: Record<string, unknown>) => ({
        ...data,
        post,
        defaults,
        processing: false,
        isDirty: false,
        errors: {},
    }));

    return { post, defaults, useForm };
});

vi.mock('@inertiajs/svelte', () => ({ useForm, router: { post: vi.fn() } }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import {
    DetailsViewModel,
    type DetailsPageData,
} from '../../../../../resources/svelte/pages/account/models/vms/DetailsViewModel.svelte';

const pageData = (): DetailsPageData => ({
    legal_name: 'Anakin Skywalker',
    display_name: 'Vader',
    phone: '555-555-5555',
    display_costume_id: 5,
    theme: 'stormtrooper',
    display_costumes: [{ value: 5, label: 'TK 1' }],
    theme_enums: [{ value: 'stormtrooper', label: 'Stormtrooper' }],
});

describe('DetailsViewModel', () => {
    beforeEach(() => {
        post.mockClear();
        defaults.mockClear();
        useForm.mockClear();
    });

    it('exposes the select options', () => {
        const subject = new DetailsViewModel(pageData());

        expect(subject.display_costumes).toEqual([{ value: 5, label: 'TK 1' }]);
        expect(subject.theme_enums).toEqual([{ value: 'stormtrooper', label: 'Stormtrooper' }]);
    });

    it('defaults the select options to empty lists', () => {
        const data = { ...pageData(), display_costumes: undefined, theme_enums: undefined };

        const subject = new DetailsViewModel(data as any);

        expect(subject.display_costumes).toEqual([]);
        expect(subject.theme_enums).toEqual([]);
    });

    it('fills the form with the profile but not the select options', () => {
        const subject = new DetailsViewModel(pageData());

        expect(subject.form.legal_name).toBe('Anakin Skywalker');
        expect(subject.form.phone).toBe('555-555-5555');
        expect(useForm.mock.calls[0][0]).not.toHaveProperty('display_costumes');
        expect(useForm.mock.calls[0][0]).not.toHaveProperty('theme_enums');
    });

    it('saves the profile and stops the browser submit', async () => {
        const event = { preventDefault: vi.fn() } as unknown as Event;
        const subject = new DetailsViewModel(pageData());

        await subject.submit(event);

        expect(event.preventDefault).toHaveBeenCalled();
        expect(post).toHaveBeenCalledWith('/account.update-profile', expect.any(Object));
    });

    it('treats the saved profile as the new baseline on success', async () => {
        const subject = new DetailsViewModel(pageData());

        await subject.submit({ preventDefault: vi.fn() } as unknown as Event);
        post.mock.calls[0][1].onSuccess({ props: { results: null } });

        expect(defaults).toHaveBeenCalled();
    });
});
