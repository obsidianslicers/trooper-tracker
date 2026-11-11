// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { form, page } = vi.hoisted(() => ({
    form: { post: vi.fn(), processing: false, isDirty: false, errors: {} },
    page: {
        props: {
            visitor_expires_at: '2026-03-05T09:30:00',
            config: { branding: { name: 'Troop Tracker' } },
            plugin_slots: {} as Record<string, string>,
        },
    },
}));

vi.mock('@inertiajs/svelte', () => ({
    useForm: () => form,
    usePage: () => page,
    router: { post: vi.fn() },
}));
vi.mock('ziggy-js', () => ({ route: (name: string) => `/${name}` }));

import PendingMembership from '../../../../../resources/svelte/pages/account/PendingMembership.svelte';
import RenewVisitor from '../../../../../resources/svelte/pages/account/RenewVisitor.svelte';

describe('PendingMembership', () => {
    beforeEach(() => (page.props.plugin_slots = {}));

    it('tells the trooper the application is under review', () => {
        render(PendingMembership);

        expect(screen.getByText('Your application is under review.')).toBeTruthy();
    });

    it('shows content plugins add to the page', () => {
        page.props.plugin_slots = { 'account.pending.sections': '<p>Welcome aboard</p>' };

        render(PendingMembership);

        expect(screen.getByText('Welcome aboard')).toBeTruthy();
    });
});

describe('RenewVisitor', () => {
    beforeEach(() => form.post.mockClear());

    it('explains when visitor access ended', () => {
        render(RenewVisitor);

        expect(screen.getByText('Your visitor access has expired')).toBeTruthy();
        expect(screen.getByText(/Mar 5, 2026/)).toBeTruthy();
    });

    it('names the application in the renewal prompt', () => {
        render(RenewVisitor);

        expect(screen.getByText(/To continue using Troop Tracker/)).toBeTruthy();
    });

    it('submits a renewal request', async () => {
        render(RenewVisitor);

        await fireEvent.click(screen.getByRole('button', { name: 'Request Renewal' }));

        expect(form.post).toHaveBeenCalledWith('/account.renew-visitor-membership', {});
    });
});
