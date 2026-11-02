import { describe, expect, it } from 'vitest';
import { parseMessage } from '../../../../resources/js/admin/parsers/parseMessage.js';

describe('parseMessage', () => {
    it('reads "Label: value" lines', () => {
        expect(parseMessage('Event Name: Movie Night\r\nVenue: Main Library\n\nComments:')).toEqual({
            'Event Name': 'Movie Night',
            'Venue': 'Main Library',
            'Comments': '',
        });
    });

    it('appends lines without a colon to the previous value', () => {
        expect(parseMessage('Venue address: 1 Main St\nOrlando, FL 32801\nEvent Start: soon')).toEqual({
            'Venue address': '1 Main St Orlando, FL 32801',
            'Event Start': 'soon',
        });
    });

    it('ignores text before the first label', () => {
        expect(parseMessage('Hello there\nEvent Name: Movie Night')).toEqual({ 'Event Name': 'Movie Night' });
    });
});
