import { describe, expect, it } from 'vitest';
import { choosePlural, makeReplacements, translate } from '$lib/translate';

describe('translate', () => {
    it('uses the first message map that has the key', () => {
        const page = { Save: 'Sichern' };
        const common = { Save: 'Speichern', Cancel: 'Abbrechen' };

        expect(translate([page, common], 'Save')).toBe('Sichern');
        expect(translate([page, common], 'Cancel')).toBe('Abbrechen');
    });

    it('falls back to the English key', () => {
        expect(translate([undefined, {}], 'Trooper Profile')).toBe('Trooper Profile');
    });

    it('fills placeholders', () => {
        expect(translate([{ 'Hello, :name': 'Hallo, :name' }], 'Hello, :name', { name: 'Rex' })).toBe('Hallo, Rex');
    });

    it('picks a plural form and fills :count', () => {
        const key = ':count troop|:count troops';

        expect(translate([], key, {}, 1)).toBe('1 troop');
        expect(translate([], key, {}, 5)).toBe('5 troops');
    });
});

describe('choosePlural', () => {
    it('supports exact and range conditions', () => {
        const line = '{0} No troops|{1} One troop|[2,*] Many troops';

        expect(choosePlural(line, 0)).toBe('No troops');
        expect(choosePlural(line, 1)).toBe('One troop');
        expect(choosePlural(line, 7)).toBe('Many troops');
    });

    it('returns a line without forms unchanged', () => {
        expect(choosePlural('Troops', 3)).toBe('Troops');
    });
});

describe('makeReplacements', () => {
    it('matches Laravel casing and prefers longer keys', () => {
        const line = ':Name, :NAME, :name, :name_full';

        expect(makeReplacements(line, { name: 'rex', name_full: 'CT-7567 Rex' }))
            .toBe('Rex, REX, rex, CT-7567 Rex');
    });
});
