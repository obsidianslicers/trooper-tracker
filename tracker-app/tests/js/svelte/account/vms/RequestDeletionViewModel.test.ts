import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post } = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { post }, useForm: vi.fn() }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import { RequestDeletionViewModel } from '../../../../../resources/svelte/pages/account/models/vms/RequestDeletionViewModel.svelte';

describe('RequestDeletionViewModel', () => {
    beforeEach(() => post.mockClear());

    it('starts closed, unconfirmed and idle', () => {
        const subject = new RequestDeletionViewModel();

        expect(subject.show_modal).toBe(false);
        expect(subject.confirm).toBe('');
        expect(subject.submitting).toBe(false);
    });

    it('posts the deletion request and stops the browser submit', () => {
        const event = { preventDefault: vi.fn() } as unknown as Event;
        const subject = new RequestDeletionViewModel();

        subject.delete(event);

        expect(event.preventDefault).toHaveBeenCalled();
        expect(post).toHaveBeenCalledWith('/account.request-deletion', {}, expect.any(Object));
        expect(subject.submitting).toBe(true);
    });

    it('is idle again once the request finishes', () => {
        const subject = new RequestDeletionViewModel();

        subject.delete({ preventDefault: vi.fn() } as unknown as Event);
        post.mock.calls[0][2].onFinish();

        expect(subject.submitting).toBe(false);
    });
});
