import { beforeEach, describe, expect, it, vi } from 'vitest';

const { form } = vi.hoisted(() => ({
    form: { post: vi.fn(), processing: false, isDirty: false, errors: {} },
}));

vi.mock('@inertiajs/svelte', () => ({ useForm: () => form, router: { post: vi.fn() } }));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import { RenewVisitorViewModel } from '../../../../../resources/svelte/pages/account/models/vms/RenewVisitorViewModel.svelte';

describe('RenewVisitorViewModel', () => {
    beforeEach(() => {
        form.post.mockClear();
        form.processing = false;
    });

    it('formats the visitor expiration', () => {
        const subject = new RenewVisitorViewModel({ visitor_expires_at: '2026-03-05T09:30:00' });

        expect(subject.visitor_expires_at).toContain('Mar 5, 2026');
    });

    it('has no expiration when none is set', () => {
        const subject = new RenewVisitorViewModel({ visitor_expires_at: null });

        expect(subject.visitor_expires_at).toBeNull();
    });

    it('posts the renewal and stops the browser submit', () => {
        const event = { preventDefault: vi.fn() } as unknown as Event;
        const subject = new RenewVisitorViewModel({ visitor_expires_at: null });

        subject.renew(event);

        expect(event.preventDefault).toHaveBeenCalled();
        expect(form.post).toHaveBeenCalledWith('/account.renew-visitor-membership', {});
    });

    it('reports submitting while the form is processing', () => {
        const subject = new RenewVisitorViewModel({ visitor_expires_at: null });
        form.processing = true;

        expect(subject.submitting).toBe(true);
    });
});
