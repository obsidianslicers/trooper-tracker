import { describe, expect, it } from 'vitest';
import { splitLinks } from '$lib/links';

describe('splitLinks', () => {
    it('leaves text without addresses as one plain run', () => {
        expect(splitLinks('I was the Vader at the front.')).toEqual([
            { text: 'I was the Vader at the front.', url: null },
        ]);
    });

    it('finds addresses between words and keeps sentence punctuation out of them', () => {
        expect(splitLinks('Photos: https://example.com/a?b=1. Thanks!')).toEqual([
            { text: 'Photos: ', url: null },
            { text: 'https://example.com/a?b=1', url: 'https://example.com/a?b=1' },
            { text: '. Thanks!', url: null },
        ]);
    });

    it('links a bare www. address over https', () => {
        expect(splitLinks('see www.example.com')).toEqual([
            { text: 'see ', url: null },
            { text: 'www.example.com', url: 'https://www.example.com' },
        ]);
    });

    it('does not link other schemes', () => {
        expect(splitLinks('javascript:alert(1)')).toEqual([
            { text: 'javascript:alert(1)', url: null },
        ]);
    });
});
