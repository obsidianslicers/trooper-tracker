export default function parseMessage(message) {
    const lines = message.split(/\r\n|\n|\r/);
    const parsed = {};
    let currentKey = null;
    for (let rawLine of lines) {
        const line = rawLine.trim();
        if (line === '') {
            continue;
        }

        if (line.includes(':')) {
            const [key, value] = line.split(':', 2).map(s => s.trim());
            currentKey = key;
            parsed[currentKey] = value;
        }
        else {
            if (currentKey !== null) {
                parsed[currentKey] += ' ' + line;
            }
        }
    }
    return parsed;
}