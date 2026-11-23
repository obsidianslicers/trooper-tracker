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

import { CreateEventViewModel } from '../../../../../../resources/svelte/pages/admin/events/models/vms/CreateEventViewModel.svelte';
import type { NewEvent } from '../../../../../../resources/svelte/pages/admin/events/models/types';

const page = (organization_id: number | null = null): NewEvent => ({
    form: {
        organization_id,
        name: '',
        event_type_id: 1,
        status: 'draft',
        event_start: null,
        event_end: null,
        event_website: null,
        latitude: null,
        longitude: null,
        venue: null,
        venue_address: null,
        contact_name: null,
        contact_phone: null,
        contact_email: null,
        expected_attendees: null,
        requested_number_characters: null,
        requested_character_types: null,
        shifts_allowed: null,
        troopers_allowed: null,
        handlers_allowed: null,
        friends_allowed: null,
        guests_allowed: null,
        tentative_signups_allowed: true,
        secure_staging_area: true,
        parking_available: true,
        allow_blasters: true,
        allow_props: true,
        accessible: true,
        amenities: null,
        comments: null,
        require_mission_brief_ack: false,
        referred_by: null,
        coordinator_trooper_id: null,
    },
    organizations: [
        { id: 4, name: '501st Legion', image_url: '', can_attend: true, troopers_allowed: null, handlers_allowed: null },
        { id: 9, name: 'Rebel Legion', image_url: '', can_attend: false, troopers_allowed: null, handlers_allowed: null },
    ],
    host_options: [
        { value: 4, label: '501st Legion', club: '501st Legion' },
        { value: 5, label: '— Florida Garrison', club: '501st Legion' },
        { value: 9, label: 'Rebel Legion', club: 'Rebel Legion' },
    ],
    event_types: [],
    status_options: [],
    smilies: [],
    plugin_fields: '',
});

const request = `Event Name: Library Star Wars Day
Venue: Main Library
Event Start: 12/13/2030 - 0930
Event End: 12/13/2030 - 1300
Expected number of attendees: 200-300
Can troopers carry blasters: No`;

describe('CreateEventViewModel', () => {
    beforeEach(() => {
        post.mockClear();
        transform.mockClear();
    });

    it('starts on the message, with the host from the page', () => {
        const subject = new CreateEventViewModel(page(5));

        expect(subject.mode).toBe('email');
        expect(subject.hasHost).toBe(true);
        expect(subject.club).toBe('501st Legion');
    });

    it("fills the form in from the host club's request message, then shows it", () => {
        const subject = new CreateEventViewModel(page(5));
        subject.form.source = request;

        subject.parseSource();

        expect(subject.mode).toBe('manual');
        expect(subject.form.name).toBe('Library Star Wars Day');
        expect(subject.form.venue).toBe('Main Library');
        expect(subject.form.event_start).toBe('2030-12-13 9:30AM');
        expect(subject.form.event_end).toBe('2030-12-13 1:00PM');
        expect(subject.form.expected_attendees).toBe('200');
        expect(subject.form.allow_blasters).toBe(false);
        expect(subject.form.source).toBe(request);
    });

    it('keeps what the message leaves out', () => {
        const subject = new CreateEventViewModel(page(5));
        subject.form.contact_name = 'Typed by hand';
        subject.form.source = request;

        subject.parseSource();

        expect(subject.form.contact_name).toBe('Typed by hand');
    });

    it('waits for a host before parsing', () => {
        const subject = new CreateEventViewModel(page());
        subject.form.source = request;

        subject.parseSource();

        expect(subject.mode).toBe('email');
        expect(subject.form.name).toBe('');
    });

    it("shows the form unfilled for a club without a parser", () => {
        const subject = new CreateEventViewModel(page(9));
        subject.form.source = request;

        subject.parseSource();

        expect(subject.mode).toBe('manual');
        expect(subject.form.name).toBe('');
    });

    it('creates the event with each club keyed by its id', () => {
        const subject = new CreateEventViewModel(page(5));
        subject.form.organizations[1].can_attend = true;
        subject.form.organizations[1].troopers_allowed = '6';

        subject.submit(new Event('submit'));

        expect(post).toHaveBeenCalledWith('/admin.events.store');
        const build = transform.mock.calls.at(-1)?.[0] as unknown as (data: unknown) => any;
        const payload = build(subject.form);
        expect(payload.organization_id).toBe(5);
        expect(payload.organizations).toEqual({
            4: { can_attend: true, troopers_allowed: '', handlers_allowed: '' },
            9: { can_attend: true, troopers_allowed: '6', handlers_allowed: '' },
        });
    });
});
