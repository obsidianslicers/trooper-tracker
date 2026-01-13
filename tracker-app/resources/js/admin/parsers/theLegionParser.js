// resources/js/parsers/theLegionParser.js

import { parseUsDateTime } from '../utils/dateUtils.js';
import { parseMessage } from './parseMessage.js';

export function theLegionParser(message) {
    const parsed = parseMessage(message);

    return {
        contact_name: parsed['Contact Name'] ?? null,
        contact_phone: parsed['Contact Phone Number'] ?? null,
        contact_email: parsed['Contact Email'] ?? null,

        name: parsed['Event Name'] ?? null,
        venue: parsed['Venue'] ?? null,
        venue_address: parsed['Venue address'] ?? null,

        event_start: parsed['Event Start'] ? parseUsDateTime(parsed['Event Start']) : null,

        event_end: parsed['Event End'] ? parseUsDateTime(parsed['Event End']) : null,

        event_website: parsed['Event Website'] ?? null,

        expected_attendees: parsed['Expected number of attendees'] ?? null,
        requested_number_characters: parsed['Requested number of characters'] ?? null,
        requested_character_types: parsed['Requested character types'] ?? null,

        secure_staging_area: (parsed['Secure changing/staging area'] ?? '') === 'Yes',

        allow_blasters: (parsed['Can troopers carry blasters'] ?? '') === 'Yes',

        allow_props: (parsed['Can troopers carry/bring props like lightsabers and staffs'] ?? '') === 'Yes',

        parking_available: (parsed['Is parking available'] ?? '') === 'Yes',

        accessible: (parsed['Is venue accessible to those with limited mobility'] ?? '') === 'Yes',

        amenities: parsed['Amenities available at venue'] ?? null,
        comments: parsed['Comments'] ?? null,
        referred_by: parsed['Referred by'] ?? null,

        source: message
    };
}
