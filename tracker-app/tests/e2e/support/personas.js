// Seeded by database/seeders/E2eSeeder.php. Every persona's password is "password".
import path from 'node:path';
import { APP_ROOT } from './env.js';

export const PASSWORD = 'password';

const AUTH_DIR = path.join(APP_ROOT, 'tests', 'e2e', '.auth');

function persona(key, email, name) {
    return { key, email, name, storageState: path.join(AUTH_DIR, `${key}.json`) };
}

export const personas = {
    admin: persona('admin', 'admin@e2e.test', 'E2E Administrator'),
    moderator: persona('moderator', 'moderator@e2e.test', 'E2E Moderator'),
    member: persona('member', 'member@e2e.test', 'E2E Member'),
    trooper: persona('trooper', 'trooper@e2e.test', 'E2E Trooper'),
};

export const seeded = {
    recruit: { email: 'recruit@e2e.test', name: 'E2E Pending Recruit' },
    unverified: { email: 'unverified@e2e.test', name: 'E2E Unverified Trooper' },
    needsSetup: { email: 'setup@e2e.test', name: 'E2E Setup Trooper' },
    denied: { email: 'denied@e2e.test', name: 'E2E Denied Recruit' },
    expiredVisitor: { email: 'expired-visitor@e2e.test', name: 'E2E Expired Visitor' },
    friend: { email: 'friend@e2e.test', name: 'E2E Trooper Friend' },
    trooperCostume: 'E2E Trooper Armor',
    signupEvent: 'E2E Signup Troop',
    briefedEvent: 'E2E Briefed Troop',
    missionBrief: 'E2E brief: report to the loading dock at nine.',
    pastParade: 'E2E Past Parade',
    mergeSource: { email: 'merge-source@e2e.test', name: 'E2E Merge Source' },
    mergeTarget: { email: 'merge-target@e2e.test', name: 'E2E Merge Target' },
    duplicateCostume: 'E2E Duplicate Armor',
    keptCostume: 'E2E Kept Armor',
    openEvent: 'E2E Open Troop',
    // OrganizationSeeder creates the 501st Legion first, on a fresh database.
    legionId: 1,
    uncountedType: { name: 'E2E Workshop', description: 'E2E armor build night.' },
    uncountedEvent: 'E2E Workshop Night',
    pastUncountedEvent: 'E2E Past Workshop',
    pastCountedEvent: 'E2E Past Hospital Visit',
    sortFirstType: 'E2E Sort First',
    sortSecondType: 'E2E Sort Second',
    retiringType: 'E2E Retiring Type',
    unusedType: 'E2E Unused Type',
};
