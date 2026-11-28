export type TextPart = { text: string; url: string | null };

// http(s) addresses and bare www. ones, up to whitespace
const LINK = /\b(?:https?:\/\/|www\.)[^\s<>"]+/gi;

// sentence punctuation typed right after an address isn't part of it
const TRAILING = /[.,;:!?)\]'"]+$/;

/**
 * Splits text a member typed into plain runs and web addresses, so a page can show the
 * addresses as links without treating the text as HTML.
 */
export function splitLinks(text: string): TextPart[] {
    const parts: TextPart[] = [];
    const pattern = new RegExp(LINK.source, LINK.flags);
    let last = 0;
    let match: RegExpExecArray | null;

    while ((match = pattern.exec(text)) !== null) {
        const address = match[0].replace(TRAILING, "");
        const start = match.index;

        if (start > last) {
            parts.push({ text: text.slice(last, start), url: null });
        }

        parts.push({ text: address, url: address.toLowerCase().startsWith("www.") ? `https://${address}` : address });
        last = start + address.length;
    }

    if (last < text.length) {
        parts.push({ text: text.slice(last), url: null });
    }

    return parts;
}
