import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { theLegionParser } from '../../../../resources/js/admin/parsers/theLegionParser.js';

const request = `Contact Name: Jane Requester
Contact Phone Number: 555-555-0100
Contact Email: requester@example.com
Event Name: Library Star Wars Day
Venue: Main Library
Venue address: 1 Main St
Orlando, FL 32801
Event Start: 12/13/2025 - 0930
Event End: 12/13/2025 - 1300
Event Website: example.com/starwars
Requested character types: Stormtroopers, Vader
Secure changing/staging area: Yes
Can troopers carry blasters: No
Can troopers carry/bring props like lightsabers and staffs: Yes
Is parking available: Yes
Is venue accessible to those with limited mobility: No
Amenities available at venue: Water
Comments: Please arrive early.
Referred by: A friend`;

describe('theLegionParser', () => {
    it('parses a request', () => {
        expect(theLegionParser(request)).toMatchObject({
            contact_name: 'Jane Requester',
            contact_phone: '555-555-0100',
            contact_email: 'requester@example.com',
            name: 'Library Star Wars Day',
            venue: 'Main Library',
            venue_address: '1 Main St Orlando, FL 32801',
            event_start: '2025-12-13 9:30AM',
            event_end: '2025-12-13 1:00PM',
            event_website: 'example.com/starwars',
            requested_character_types: 'Stormtroopers, Vader',
            secure_staging_area: true,
            allow_blasters: false,
            allow_props: true,
            parking_available: true,
            accessible: false,
            amenities: 'Water',
            comments: 'Please arrive early.',
            referred_by: 'A friend',
            source: request,
        });
    });

    it('leaves missing fields empty', () => {
        expect(theLegionParser('Event Name: Movie Night')).toMatchObject({
            contact_name: null,
            name: 'Movie Night',
            venue_address: null,
            event_start: null,
            event_end: null,
            secure_staging_area: false,
            parking_available: false,
        });
    });
});

describe('theLegionParser with real-world requests', () => {
    beforeEach(() => {
        vi.useFakeTimers({ toFake: ['Date'] });
        vi.setSystemTime(new Date(2026, 10, 1));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('keeps values that contain a colon', () => {
        expect(theLegionParser('Event Start: 12/13/2025 - 15:00\nEvent Website: https://example.com/starwars')).toMatchObject({
            event_start: '2025-12-13 3:00PM',
            event_website: 'https://example.com/starwars',
        });
    });

    it('reads counts as whole numbers', () => {
        expect(theLegionParser('Expected number of attendees: 200-300\nRequested number of characters: 10')).toMatchObject({
            expected_attendees: 200,
            requested_number_characters: 10,
        });
        expect(theLegionParser('Expected number of attendees: lots\nRequested number of characters: ?')).toMatchObject({
            expected_attendees: null,
            requested_number_characters: null,
        });
    });

    it('parses a request with the shorter labels some garrison forms use', () => {
        const request = `Contact: Jane Requester
Phone #: 555.555.0100
Email: requester@example.com
Event: Pack 123 Blue & Gold Banquet
Date: Saturday, February 27th
Venue: Example Middle School
Address: 100 Main St. Springfield, IL. 62701
Event Start: 2027-02-27 - 15:00
Event End: 2027-02-27 - 18:00
Requested character types:

Secure changing/staging area: Yes`;

        expect(theLegionParser(request)).toMatchObject({
            contact_name: 'Jane Requester',
            contact_phone: '555.555.0100',
            contact_email: 'requester@example.com',
            name: 'Pack 123 Blue & Gold Banquet',
            venue: 'Example Middle School',
            venue_address: '100 Main St. Springfield, IL. 62701',
            event_start: '2027-02-27 3:00PM',
            event_end: '2027-02-27 6:00PM',
            requested_character_types: '',
            secure_staging_area: true,
        });
    });

    it('prefers the standard label when both are present', () => {
        expect(theLegionParser('Event Name: Standard\nEvent: Short').name).toBe('Standard');
    });

    it('parses a request copied from its forum post', () => {
        const post = `[quote][b]Contact Name:[/b] Jane Requester
[b]Contact Email:[/b] [email]requester@example.com[/email]
[b]Event Name:[/b] [color=#ff0000]Movie Night[/color]
[b]Event Start:[/b] 6/12/27  - 6:30
[b]Event End:[/b]  - 8:30
[b]Expected number of attendees:[/b] 1,500+
[b]Is parking available:[/b] Not specified
[b]Can troopers carry blasters:[/b] Yes, but no realistic ones[/quote]`;

        expect(theLegionParser(post)).toMatchObject({
            contact_name: 'Jane Requester',
            contact_email: 'requester@example.com',
            name: 'Movie Night',
            // no am/pm, and the end has no date: an evening on the start's day
            event_start: '2027-06-12 6:30PM',
            event_end: '2027-06-12 8:30PM',
            expected_attendees: 1500,
            parking_available: false,
            allow_blasters: true,
        });
    });

    it('moves a no-am/pm end that falls before the start into the evening', () => {
        const parsed = theLegionParser('Event Start: 6/12/2027 - 10:00\nEvent End: 6/12/2027 - 9:00');

        expect(parsed.event_start).toBe('2027-06-12 10:00AM');
        expect(parsed.event_end).toBe('2027-06-12 9:00PM');
    });

    it('leaves an overnight end alone', () => {
        expect(theLegionParser('Event Start: 6/12/2027 - 10pm\nEvent End: 6/12/2027 - 2am').event_end).toBe('2027-06-12 2:00AM');
    });

    it('leaves an unreadable date empty', () => {
        expect(theLegionParser('Event Start: TBD\nEvent End: - 5pm')).toMatchObject({ event_start: null, event_end: null });
    });
});
