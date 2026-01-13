import { parseMessage } from './parseMessage.js';
import { parseTime, parseUsDate, toInt } from './parseUtils.js';

export function mandalorianMercsParser(message) {
    const parsed = parseMessage(message);

    // -----------------------------
    // Parse event dates
    // Format: "12/13/2025 to 12/13/2025"
    // -----------------------------
    let startDate = null;
    let endDate = null;

    if (parsed['Event Date(s)']) {
        const dates = parsed['Event Date(s)'].split('to');
        const startRaw = dates[0] ? dates[0].trim() : null;
        const endRaw = dates[1] ? dates[1].trim() : null;

        // Convert m/d/Y → JS Date
        startDate = startRaw ? parseUsDate(startRaw) : null;
        endDate = endRaw ? parseUsDate(endRaw) : null;

        // Optional start time
        if (startDate && parsed['Start time']) {
            const t = parseTime(parsed['Start time']);
            if (t) {
                startDate.setHours(t.hour, t.minute, 0, 0);
            }
        }

        // Optional end time
        if (endDate && parsed['End time']) {
            const t = parseTime(parsed['End time']);
            if (t) {
                endDate.setHours(t.hour, t.minute, 0, 0);
            }
        }
    }

    // -----------------------------
    // Normalize Mercs multi-line location
    // -----------------------------
    let venueAddress = null;
    if (parsed['Event Location']) {
        venueAddress = parsed['Event Location']
            .replace(/\s+/g, ' ')
            .trim();
    }

    // -----------------------------
    // Return event object for Alpine → Laravel POST
    // -----------------------------
    return {
        contact_name: parsed['Name'] ?? null,
        contact_phone: parsed['Phone'] ?? null,
        contact_email: parsed['Email'] ?? null,

        name: parsed['Event Name'] ?? null,
        venue: parsed['Event Name'] ?? null, // Mercs do not provide separate venue name
        venue_address: venueAddress,

        event_start: startDate,
        event_end: endDate,

        event_website: parsed['Website'] ?? null,

        expected_attendees: toInt(parsed['Number of attendees']),

        secure_staging_area:
            (parsed['Can provide a safe and secure changing area?'] ?? '') === 'Yes',

        allow_blasters:
            (parsed['Are our members allowed to carry prop/simulated firearms weapons such as blasters at your event?'] ?? '') === 'Yes',

        allow_props:
            (parsed['Are our members allowed to carry prop/simulated melee weapons such as axes, knives, swords, or spears at your event?'] ?? '') === 'Yes',

        comments: parsed['Event Description'] ?? null,
        referred_by: parsed['How did you hear about us?'] ?? null,

        source: message
    };
}