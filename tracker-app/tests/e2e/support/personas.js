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
    account: persona('account', 'account@e2e.test', 'E2E Account Trooper'),
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
    rostered: { email: 'rostered@e2e.test', name: 'E2E Rostered Trooper' },
    commandEvent: 'E2E Command Troop',
    cancelEvent: 'E2E Cancel Troop',
    copySourceEvent: 'E2E Copy Source',
    spareCostume: 'E2E Spare Armor',
    notice: 'E2E Armor Inspection Notice',
    recruitToDeny: { email: 'deny-recruit@e2e.test', name: 'E2E Recruit To Deny' },
    clubJoiner: { email: 'club-join@e2e.test', name: 'E2E Club Joiner' },
    clubRefused: { email: 'club-refused@e2e.test', name: 'E2E Club Refused' },
    promotionCandidate: 'E2E Promotion Candidate',
    promotionRefused: 'E2E Promotion Refused',
    profileSubject: 'E2E Profile Subject',
    authoritySubject: 'E2E Authority Subject',
    membershipSubject: { email: 'membership-subject@e2e.test', name: 'E2E Membership Subject' },
    cadet: 'E2E Cadet',
    guardian: { email: 'guardian@e2e.test', name: 'E2E Guardian' },
    voided: 'E2E Voided Trooper',
    departed: 'E2E Departed Trooper',
    untrusted: 'E2E Untrusted Subject',
    recruitTarget: 'E2E Recruit Target',
    costumeOwner: 'E2E Costume Owner',
    doomedCostume: 'E2E Doomed Armor',
    revokedCostume: 'E2E Revoked Armor',
    pendingCostume: 'E2E Pending Armor',
    refusedCostume: 'E2E Refused Armor',
    renamedCostume: 'E2E Renamed Armor',
    newCostume: 'E2E Brand New Armor',
    untrustedTrooper: { email: 'untrusted@e2e.test', name: 'E2E Untrusted Trooper' },
    untrustedCostume: 'E2E Untrusted Armor',
    verifiedParade: 'E2E Verified Parade',
    disputedParade: 'E2E Disputed Parade',
    mergeSource: { email: 'merge-source@e2e.test', name: 'E2E Merge Source' },
    mergeTarget: { email: 'merge-target@e2e.test', name: 'E2E Merge Target' },
    duplicateCostume: 'E2E Duplicate Armor',
    keptCostume: 'E2E Kept Armor',
    openEvent: 'E2E Open Troop',
    // OrganizationSeeder creates the 501st Legion first, on a fresh database.
    legionId: 1,
    // The administrator is the first trooper E2eSeeder creates.
    adminId: 1,
    uncountedType: { name: 'E2E Workshop', description: 'E2E armor build night.' },
    uncountedEvent: 'E2E Workshop Night',
    pastUncountedEvent: 'E2E Past Workshop',
    pastCountedEvent: 'E2E Past Hospital Visit',
    sortFirstType: 'E2E Sort First',
    sortSecondType: 'E2E Sort Second',
    retiringType: 'E2E Retiring Type',
    unusedType: 'E2E Unused Type',
};
