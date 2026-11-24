import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post } = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { post } }));
vi.mock('ziggy-js', () => ({
    route: (name: string, params?: Record<string, unknown>) => `/${name}/${params?.notice ?? ''}`,
}));

import { NoticesViewModel } from '../../../../../resources/svelte/pages/account/models/vms/NoticesViewModel.svelte';
import type { UnreadNotice } from '../../../../../resources/svelte/lib/domains/types.svelte';

const notice = (id: number): UnreadNotice => ({
    id,
    type: 'info',
    heading: 'Information',
    title: `Notice ${id}`,
    html: '<p>Hello</p>',
});

describe('NoticesViewModel', () => {
    beforeEach(() => post.mockClear());

    it('lists the unread notices', () => {
        const subject = new NoticesViewModel({ notices: [notice(1), notice(2)] });

        expect(subject.notices.map((n) => n.id)).toEqual([1, 2]);
    });

    it('hides a dismissed notice at once and marks it read', () => {
        const subject = new NoticesViewModel({ notices: [notice(1), notice(2)] });

        subject.dismiss(subject.notices[0]);

        expect(subject.notices.map((n) => n.id)).toEqual([2]);
        expect(post).toHaveBeenCalledWith('/account.update-notice-as-read/1', {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['flash'],
        });
    });
});
