import { describe, expect, it } from 'vitest';
import { mandalorianMercsParser } from '../../../../resources/js/admin/parsers/mandalorianMercsParser.js';

const request = `Name: Jane Requester
Phone: 555-555-0100
Email: requester@example.com
Event Name: Fall Festival
Event Date(s): 12/13/2025 to 12/14/2025
Event Location: 1 Main St
Orlando, FL 32801
Website: example.com/festival
Number of attendees: 500
Can provide a safe and secure changing area?: Yes
Event Description: A family festival.
How did you hear about us?: A friend`;

describe('mandalorianMercsParser', () => {
    it('parses a request', () => {
        expect(mandalorianMercsParser(request)).toEqual({
            contact_name: 'Jane Requester',
            contact_phone: '555-555-0100',
            contact_email: 'requester@example.com',
            name: 'Fall Festival',
            venue: 'Fall Festival',
            venue_address: '1 Main St Orlando, FL 32801',
            event_start: new Date(2025, 11, 13),
            event_end: new Date(2025, 11, 14),
            event_website: 'example.com/festival',
            expected_attendees: 500,
            secure_staging_area: true,
            allow_blasters: false,
            allow_props: false,
            comments: 'A family festival.',
            referred_by: 'A friend',
            source: request,
        });
    });
});
