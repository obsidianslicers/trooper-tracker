import { describe, expect, it } from 'vitest';
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
