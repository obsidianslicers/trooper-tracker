// resources/js/parsers/theLegionParser.js

import { parseMessage } from './parseMessage.js';
import { firstInt, formatUsDateTime, parseUsDateTime } from './parseUtils.js';

export function theLegionParser(message) {
    // a request copied from its forum post keeps the post's BBCode
    const parsed = parseMessage(message.replace(/\[\/?(b|i|u|s|color|size|font|url|email|quote|img|center|left|right)(=[^\]]*)?\]/gi, ''));

    // garrison request forms label some fields differently; the first label present wins
    const field = (...labels) => labels.map(label => parsed[label]).find(value => value !== undefined) ?? null;

    // answers are "Yes", "No", "Not specified" or "Yes, but ..."
    const yes = label => /^yes\b/i.test(parsed[label] ?? '');

    const eventStart = parseUsDateTime(parsed['Event Start']);

    // an end without a date ("- 5pm") is on the start's day
    let eventEnd = parseUsDateTime(parsed['Event End'], eventStart);

    // "6:30" to "8:30" with no am/pm: an end before the start is in the evening
    if (eventStart && eventEnd && eventEnd <= eventStart && eventEnd.getHours() < 12) {
        const evening = new Date(eventEnd);
        evening.setHours(evening.getHours() + 12);
        eventEnd = evening > eventStart ? evening : eventEnd;
    }

    return {
        contact_name: field('Contact Name', 'Contact'),
        contact_phone: field('Contact Phone Number', 'Phone #'),
        contact_email: field('Contact Email', 'Email'),

        name: field('Event Name', 'Event'),
        venue: parsed['Venue'] ?? null,
        venue_address: field('Venue address', 'Address'),

        event_start: formatUsDateTime(eventStart),

        event_end: formatUsDateTime(eventEnd),

        event_website: parsed['Event Website'] ?? null,

        // requesters write "200-300", "1,000+" or "?"; the fields take a whole number
        expected_attendees: firstInt(parsed['Expected number of attendees']),
        requested_number_characters: firstInt(parsed['Requested number of characters']),
        requested_character_types: parsed['Requested character types'] ?? null,

        secure_staging_area: yes('Secure changing/staging area'),

        allow_blasters: yes('Can troopers carry blasters'),

        allow_props: yes('Can troopers carry/bring props like lightsabers and staffs'),

        parking_available: yes('Is parking available'),

        accessible: yes('Is venue accessible to those with limited mobility'),

        amenities: parsed['Amenities available at venue'] ?? null,
        comments: parsed['Comments'] ?? null,
        referred_by: parsed['Referred by'] ?? null,

        source: message
    };
}
