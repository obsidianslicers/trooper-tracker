// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';

const router = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router }));

import {
    EventDisplayViewModel,
    LONG_ROSTER,
    SHIFT_PROPS,
    guestRowKey,
    trooperRowKey,
} from '../../../../../resources/svelte/pages/events/models/vms/EventDisplayViewModel.svelte';
import type { DisplayShift, TrooperRow } from '../../../../../resources/svelte/pages/events/models/display-types';

const row = (id: number, overrides: Partial<TrooperRow> = {}): TrooperRow => ({
    id,
    number: 1,
    trooper: { name: `TK-${id}`, legal_name: `Trooper ${id}`, url: `/troopers/${id}` },
    is_mine: false,
    needs_verification: false,
    added_by: null,
    status: { value: 'going', label: 'Going', color: 'text-success' },
    costume: null,
    station: null,
    approved_by: null,
    actions: {
        update_url: `/events/signup-update/${id}`,
        approve: false,
        reject: false,
        needs_costume_to_approve: null,
        sign_up_again: null,
        attendance: null,
    },
    editor: null,
    opens_itself: false,
    ...overrides,
});

const shift = (id: number, overrides: Partial<DisplayShift> = {}): DisplayShift => ({
    id,
    label: `Shift ${id}`,
    is_open: true,
    is_mine: false,
    status_label: null,
    fullness: null,
    stations: [],
    same_day: null,
    going: [],
    stand_by: [],
    cancelled: [],
    guests: [],
    sign_up: null,
    links: {
        friend_form_url: `/events/add-trooper-form/${id}`,
        can_add_friend: false,
        can_add_guest: false,
        guest_url: `/events/guest-signup/${id}`,
        signup_url: `/events/signup/${id}`,
        calendar: null,
        csv_url: null,
    },
    ...overrides,
});

describe('EventDisplayViewModel', () => {
    beforeEach(() => {
        router.post.mockReset();
    });

    it('opens the only shift, and the shifts the viewer is on', () => {
        expect(new EventDisplayViewModel([shift(1)]).isOpen(1)).toBe(true);

        const vm = new EventDisplayViewModel([shift(1), shift(2, { is_mine: true })]);

        expect(vm.isOpen(1)).toBe(false);
        expect(vm.isOpen(2)).toBe(true);
    });

    it('opens a row that still needs a costume, once, so closing it sticks', () => {
        const pending = row(7, { opens_itself: true });
        const vm = new EventDisplayViewModel([shift(1), shift(2, { going: [pending] })]);

        expect(vm.isEditing(trooperRowKey(7))).toBe(true);
        expect(vm.isOpen(2)).toBe(true);

        vm.toggleEdit(trooperRowKey(7));
        vm.sync([shift(1), shift(2, { going: [pending] })]);

        expect(vm.isEditing(trooperRowKey(7))).toBe(false);
    });

    it('opens a shift the viewer has just joined, and keeps the ones they closed closed', () => {
        const vm = new EventDisplayViewModel([shift(1), shift(2)]);
        vm.toggleShift(1);
        vm.toggleShift(1);

        vm.sync([shift(1), shift(2, { is_mine: true })]);

        expect(vm.isOpen(1)).toBe(false);
        expect(vm.isOpen(2)).toBe(true);
    });

    it('toggles shift cards and edit areas', () => {
        const vm = new EventDisplayViewModel([shift(1), shift(2)]);

        vm.toggleShift(2);
        vm.toggleEdit(guestRowKey(3));

        expect(vm.isOpen(2)).toBe(true);
        expect(vm.isEditing('guest-3')).toBe(true);

        vm.toggleShift(2);
        vm.toggleEdit(guestRowKey(3));

        expect(vm.isOpen(2)).toBe(false);
        expect(vm.isEditing('guest-3')).toBe(false);
    });

    it('posts a change and reloads only the shifts and the summary', () => {
        const vm = new EventDisplayViewModel([shift(1)]);

        vm.send('/events/signup/1', { costume_id: '5' }, { shift_id: 1 });

        const [url, data, options] = router.post.mock.calls[0];
        expect(url).toBe('/events/signup/1');
        expect(data).toEqual({ costume_id: '5' });
        expect(options.only).toEqual(SHIFT_PROPS);
        expect(options.preserveScroll).toBe(true);

        options.onStart();
        expect(vm.busy_shift).toBe(1);
        expect(vm.sending).toBe(true);

        options.onFinish();
        expect(vm.busy_shift).toBeNull();
        expect(vm.sending).toBe(false);
    });

    it('hands back field errors', () => {
        const vm = new EventDisplayViewModel([]);
        const onError = vi.fn();

        vm.send('/events/share-roster/1', {}, { only: ['flash'], onError });
        router.post.mock.calls[0][2].onError({ recipient_email: 'Bad address' });

        expect(onError).toHaveBeenCalledWith({ recipient_email: 'Bad address' });
    });

    it('knows which button sent the request on its way', () => {
        const vm = new EventDisplayViewModel([]);

        vm.send('/events/update/1/trooper', { status: 'cancelled' }, { shift_id: 1, busy: 'cancel-1' });
        const options = router.post.mock.calls[0][2];
        expect(vm.isBusy('cancel-1')).toBe(false);

        options.onStart();
        expect(vm.isBusy('cancel-1')).toBe(true);
        expect(vm.isBusy('sign-up-1')).toBe(false);

        options.onFinish();
        expect(vm.isBusy('cancel-1')).toBe(false);
    });

    it('runs a confirmed action once, and drops a cancelled one', () => {
        const vm = new EventDisplayViewModel([]);
        const run = vi.fn();

        vm.confirm({ title: 'Cancel', message: 'Sure?', label: 'Cancel', danger: true, run });
        vm.cancelConfirmation();
        expect(vm.confirmation).toBeNull();
        expect(run).not.toHaveBeenCalled();

        vm.confirm({ title: 'Cancel', message: 'Sure?', label: 'Cancel', danger: true, run });
        vm.runConfirmation();
        vm.runConfirmation();

        expect(run).toHaveBeenCalledOnce();
        expect(vm.confirmation).toBeNull();
    });

    it('opens the summary\'s sign-up row and the shifts to sign up for', async () => {
        const vm = new EventDisplayViewModel([shift(1), shift(2)]);

        await vm.editSignUp(9, 2);
        await vm.goToSignUp([1]);

        expect(vm.isOpen(1)).toBe(true);
        expect(vm.isOpen(2)).toBe(true);
        expect(vm.isEditing(trooperRowKey(9))).toBe(true);
    });

    it('counts going and stand-by, not guests or cancelled, toward a long roster', () => {
        const vm = new EventDisplayViewModel([]);
        const rows = (from: number, count: number) => Array.from({ length: count }, (_, i) => row(from + i));

        expect(vm.hasLongRoster(shift(1, { going: rows(1, LONG_ROSTER) }))).toBe(false);
        expect(vm.hasLongRoster(shift(1, { going: rows(1, 4), stand_by: rows(10, 3) }))).toBe(true);
        expect(vm.hasLongRoster(shift(1, { going: rows(1, 3), cancelled: rows(10, 9) }))).toBe(false);
    });

    it('hands a sign-up request to its shift once', () => {
        const vm = new EventDisplayViewModel([shift(1), shift(2)]);

        vm.requestSignUp(2);

        expect(vm.takeSignUpRequest(1)).toBe(false);
        expect(vm.takeSignUpRequest(2)).toBe(true);
        expect(vm.takeSignUpRequest(2)).toBe(false);
        expect(vm.sign_up_requested).toBeNull();
    });

    it("sends the top Sign up to the shift's own Sign up when there is one shift to sign up for", async () => {
        const vm = new EventDisplayViewModel([shift(1)]);

        const going = vm.goToSignUp([1]);

        expect(vm.isOpen(1)).toBe(true);
        expect(vm.sign_up_requested).toBe(1);
        await going;
        //  nothing on the page took it up here, so it doesn't linger for later
        expect(vm.sign_up_requested).toBeNull();
    });

    it('leaves the shift to the trooper when several can be signed up for', async () => {
        const vm = new EventDisplayViewModel([shift(1), shift(2)]);

        const going = vm.goToSignUp([1, 2]);

        expect(vm.sign_up_requested).toBeNull();
        await going;
        expect(vm.isOpen(1) && vm.isOpen(2)).toBe(true);
    });
});
