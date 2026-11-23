import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post, transform, useForm } = vi.hoisted(() => {
    const post = vi.fn();
    const transform = vi.fn(() => ({ post }));
    const useForm = vi.fn((data: Record<string, unknown>) => ({
        ...data,
        transform,
        post,
        processing: false,
        errors: {},
    }));

    return { post, transform, useForm };
});

vi.mock('@inertiajs/svelte', () => ({ useForm, router: { post: vi.fn() }, page: { props: {} } }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import { ShiftViewModel } from '../../../../../../resources/svelte/pages/admin/events/models/vms/ShiftViewModel.svelte';
import type { EventStation } from '../../../../../../resources/svelte/pages/admin/events/models/types';

const shift = (stations: EventStation[] = []) => ({
    date: '2026-12-05',
    starts_at: '10:00',
    ends_at: '14:00',
    stations,
});

/** The request body the last save sent, built by the form's transform callback. */
const lastPayload = (subject: ShiftViewModel) => {
    const build = transform.mock.calls.at(-1)?.[0] as unknown as (data: unknown) => any;

    return build(subject.form);
};

describe('ShiftViewModel stations', () => {
    beforeEach(() => {
        post.mockClear();
        transform.mockClear();
        useForm.mockClear();
    });

    it('loads saved capacities as numbers for the number input', () => {
        const subject = new ShiftViewModel(1, 7, shift([{ id: 3, name: 'Entrance', troopers_allowed: '12' }]));

        expect(subject.form.stations[0].troopers_allowed).toBe(12);
    });

    it('waits for a new station to have a capacity before saving it', () => {
        const subject = new ShiftViewModel(1, 7, shift());

        subject.addStation();
        const station = subject.form.stations[0];
        station.name = 'Photo line';
        subject.changeStation(station);

        expect(post).not.toHaveBeenCalled();

        station.troopers_allowed = 4;
        subject.changeStation(station);

        expect(post).toHaveBeenCalledWith('/admin.events.shifts.update', expect.any(Object));
        expect(lastPayload(subject).shifts[7].stations[station.key]).toEqual({
            name: 'Photo line',
            troopers_allowed: 4,
        });
    });

    it('leaves out a new station whose capacity was cleared', () => {
        const subject = new ShiftViewModel(1, 7, shift([{ id: 3, name: 'Entrance', troopers_allowed: '12' }]));

        subject.addStation();
        subject.form.stations[1].name = 'Photo line';
        subject.form.stations[1].troopers_allowed = null;
        subject.change();

        expect(Object.keys(lastPayload(subject).shifts[7].stations)).toEqual(['3']);
    });
});
