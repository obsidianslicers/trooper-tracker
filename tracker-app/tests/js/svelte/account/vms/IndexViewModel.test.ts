import { describe, expect, it } from 'vitest';

import {
    IndexViewModel,
    type IndexPageData,
} from '../../../../../resources/svelte/pages/account/models/vms/IndexViewModel.svelte';

const pageData = (overrides: Partial<IndexPageData> = {}): IndexPageData => ({
    trooper_id: 1,
    is_visitor: false,
    is_handler: false,
    deletion_requested_at: null,
    visitor_expires_at: null,
    email: 'trooper@example.com',
    details: {} as any,
    notifications: {} as any,
    memberships: {} as any,
    costumes: [],
    friends: [],
    minors: [],
    ...overrides,
});

describe('IndexViewModel', () => {
    it('has no deletion request or date by default', () => {
        const subject = new IndexViewModel(pageData());

        expect(subject.has_deletion_request).toBe(false);
        expect(subject.deletion_date).toBeNull();
    });

    it('gives thirty days from the request before deletion', () => {
        const subject = new IndexViewModel(
            pageData({ deletion_requested_at: '2026-01-01T12:00:00' }),
        );

        expect(subject.has_deletion_request).toBe(true);
        expect(subject.deletion_date).toBe('Jan 31, 2026');
    });

    it('formats the visitor expiration', () => {
        const subject = new IndexViewModel(
            pageData({ visitor_expires_at: '2026-03-05T09:30:00' }),
        );

        expect(subject.visitation_expiration_datetime).toContain('Mar 5, 2026');
    });

    it('has no visitor expiration when none is set', () => {
        const subject = new IndexViewModel(pageData());

        expect(subject.visitation_expiration_datetime).toBeNull();
    });

    it('reports whether there are minors and friends', () => {
        const person = { id: 1, legal_name: 'A', display_name: 'B' };

        const empty = new IndexViewModel(pageData());
        const populated = new IndexViewModel(pageData({ friends: [person], minors: [person] }));

        expect(empty.has_minors).toBe(false);
        expect(empty.has_friends).toBe(false);
        expect(populated.has_minors).toBe(true);
        expect(populated.has_friends).toBe(true);
    });

    it('exposes the trooper role flags and email', () => {
        const subject = new IndexViewModel(pageData({ is_visitor: true, is_handler: true }));

        expect(subject.is_visitor).toBe(true);
        expect(subject.is_handler).toBe(true);
        expect(subject.email).toBe('trooper@example.com');
    });
});
