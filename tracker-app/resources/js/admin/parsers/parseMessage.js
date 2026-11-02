export function parseMessage(message) {
    const lines = message.split(/\r\n|\n|\r/);
    const parsed = {};
    let currentKey = null;
    for (let rawLine of lines) {
        const line = rawLine.trim();
        if (line === '') {
            continue;
        }

        if (line.includes(':')) {
            // split at the first colon only; values like times and URLs have their own
            const colon = line.indexOf(':');
            currentKey = line.slice(0, colon).trim();
            parsed[currentKey] = line.slice(colon + 1).trim();
        }
        else {
            if (currentKey !== null) {
                parsed[currentKey] += ' ' + line;
            }
        }
    }
    return parsed;
}