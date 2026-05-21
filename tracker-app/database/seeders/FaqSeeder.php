<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $s = FaqSection::pluck('id', 'label');

        $reg   = $s['Getting Started & Registration'];
        $acct  = $s['Account Types'];
        $orgs  = $s['Organizations & Club Memberships'];
        $cost  = $s['Costumes'];
        $evt   = $s['Events'];
        $sigup = $s['Signing Up for Events'];
        $guest = $s['Guests'];
        $frnd  = $s['Friends'];
        $vid   = $s['How-To Videos'];

        $items = [

            // ── Registration ──────────────────────────────────────────────
            [
                'section_id'  => $reg,
                'title'       => 'How do I create an account?',
                'description' => <<<'MD'
1. Click **Sign Up** in the top-right corner of the navbar.
2. Choose your sign-up method: **Email**, **Google**, or **XenForo** (if your garrison's installation supports it).
3. Fill in the registration form — see the fields below for details.
4. Select your **Account Type** and **Organization(s)**.
5. Submit the form and wait for **Command Staff approval**.
6. You'll receive an email notification once your account is activated.
MD,
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $reg,
                'title'       => 'What information do I need to register?',
                'description' => <<<'MD'
- **Legal Name** — used for official records and shared with event coordinators for safety purposes; not displayed publicly.
- **Display Name** — the name shown publicly on your profile and the event roster.
- **Email** — used for account communications (pre-filled if you sign up via Google or XenForo).
- **Phone** — optional; used for event coordinator contact.
- **Date of Birth & Guardian Email** — required only if you are under 18. Your guardian must already have an active account on the tracker.
- **Password** — required only if you choose the Email sign-up method.
MD,
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $reg,
                'title'       => 'How long does account approval take?',
                'description' => 'Approval times vary by garrison and Command Staff availability. Most accounts are reviewed within a few days. You\'ll receive an email when your account is approved and active. If you haven\'t heard back after a week, reach out to your garrison\'s Command Staff directly.',
                'sort_order'  => 3,
            ],
            [
                'section_id'  => $reg,
                'title'       => 'Can I sign up with my existing forum account?',
                'description' => 'Yes — if your garrison has XenForo integration configured, you can use **Sign Up with XenForo** to link your existing forum account. This avoids creating a separate password and keeps your identities connected.',
                'sort_order'  => 4,
            ],

            // ── Account Types ─────────────────────────────────────────────
            [
                'section_id'  => $acct,
                'title'       => 'What are the different account types?',
                'description' => <<<'MD'
**Member** — Active costumed member of a Star Wars costuming organization. Can sign up for event shifts, select a costume for each shift, and track costume hours in their service record.

**Handler** — Handler for a costumed member. No costume is required. Handlers sign up for shifts and appear on the event roster alongside the trooper they support.

**Visitor** — Temporary access valid for 6 months. Visitors are assigned to the top-level organization only — no region or unit selection is required. Useful for visiting members.
MD,
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $acct,
                'title'       => 'Can I change my account type after registering?',
                'description' => 'Account type changes require Command Staff approval. Contact your garrison\'s leadership to request a type change (for example, upgrading from Visitor to Member once you change garrisons).',
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $acct,
                'title'       => 'My Visitor access is expiring — how do I renew it?',
                'description' => 'Go to **Account → Setup**. If your visitor period is approaching expiration, you\'ll see a renewal option there. Renewals are granted at Command Staff discretion.',
                'sort_order'  => 3,
            ],

            // ── Organizations ─────────────────────────────────────────────
            [
                'section_id'  => $orgs,
                'title'       => 'How does the organization hierarchy work?',
                'description' => <<<'MD'
Organizations follow a three-level hierarchy:

1. **Organization** — the top-level club (e.g., 501st Legion, Rebel Legion)
2. **Region / Garrison** — a regional chapter within the organization
3. **Unit / Squad** — a local sub-group within the region
MD,
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $orgs,
                'title'       => 'How do I select my organization during registration?',
                'description' => <<<'MD'
1. On the registration form, check the box next to each organization you belong to.
2. Enter your member ID (e.g., TK number) for that organization if applicable.
3. Select your **Region/Garrison** from the dropdown.
4. Select your **Unit/Squad** once a region is chosen.
MD,
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $orgs,
                'title'       => 'Can I belong to multiple organizations?',
                'description' => 'Yes. Many members hold dual or triple membership (e.g., 501st Legion and Rebel Legion). Check all applicable organizations on the registration form. Each membership is reviewed and approved independently by Command Staff.',
                'sort_order'  => 3,
            ],
            [
                'section_id'  => $orgs,
                'title'       => 'How do I update my club memberships after registration?',
                'description' => 'Go to **Account → Club Memberships**. You can add new organizations or update your region/unit there. Changes require Command Staff re-approval before they become active.',
                'sort_order'  => 4,
            ],

            // ── Costumes ──────────────────────────────────────────────────
            [
                'section_id'  => $cost,
                'title'       => 'How do I add a costume to my profile?',
                'description' => <<<'MD'
1. Navigate to **Account → Costumes**.
2. Use the search box to find your costume type by name (e.g., "Stormtrooper", "Rebel Fleet Trooper").
3. Click the costume in the results to add it to your profile.
4. Repeat for each costume you own.
MD,
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $cost,
                'title'       => 'Can I have more than one costume?',
                'description' => 'Absolutely. Add as many costumes as you own. When signing up for an event shift, you\'ll select which costume you\'ll be wearing for that specific shift.',
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $cost,
                'title'       => 'How do I remove a costume from my profile?',
                'description' => 'Go to **Account → Costumes**, find the costume in your list, and use the delete action to remove it.',
                'sort_order'  => 3,
            ],
            [
                'section_id'  => $cost,
                'title'       => 'My costume isn\'t in the list — what do I do?',
                'description' => 'Costume types are managed by Command Staff. If your costume isn\'t available, contact your garrison administrator to have it added to the system.',
                'sort_order'  => 4,
            ],

            // ── Events ────────────────────────────────────────────────────
            [
                'section_id'  => $evt,
                'title'       => 'How do I find upcoming events?',
                'description' => <<<'MD'
Click **Events** in the navbar. You'll find three views:

- **List** — a filterable table of all upcoming events
- **Calendar** — a monthly calendar view of events
- **Map** — an interactive map showing event locations
MD,
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $evt,
                'title'       => 'What information is shown on an event page?',
                'description' => <<<'MD'
- Event name, date, and location
- Organizing group or beneficiary
- Available shifts (time slots) with roster capacity
- Current roster — who is signed up and what costume they're wearing
- Mission Brief with any important event-specific information
MD,
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $evt,
                'title'       => 'What is a shift?',
                'description' => 'A shift is a specific time slot within an event. Some events have a single shift covering the whole appearance; others split into multiple shifts (e.g., morning and afternoon). You sign up for the shift(s) you can attend, not the event as a whole.',
                'sort_order'  => 3,
            ],
            [
                'section_id'  => $evt,
                'title'       => 'What is a standby list?',
                'description' => 'Each shift has a maximum roster capacity. If a shift is full when you sign up, you\'ll be placed on the standby list. If a spot opens up, standby troopers may be moved to the active roster. You\'ll still appear on the event page so coordinators know you\'re available.',
                'sort_order'  => 4,
            ],

            // ── Signing Up ────────────────────────────────────────────────
            [
                'section_id'  => $sigup,
                'title'       => 'How do I sign up for an event shift?',
                'description' => <<<'MD'
1. Open the event page and locate the shift you want to attend.
2. Click the **Sign Up** button on that shift.
3. Select the **costume** you'll be wearing from your profile.
4. Choose your **role** if applicable (e.g., character role, handler).
5. Read and acknowledge the **Mission Brief**.
6. You'll be added to the active roster (or standby if the shift is full).
MD,
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $sigup,
                'title'       => 'Can I update my signup after the fact?',
                'description' => 'Yes. Return to the event page and use the update option on your shift entry to change your costume selection or role. Updates may be restricted closer to the event date.',
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $sigup,
                'title'       => 'How do I withdraw from a shift?',
                'description' => 'Open the event page and find your entry on the shift roster. Use the remove/withdraw option. If you\'re withdrawing close to the event date, please notify your event coordinator directly so they can adjust plans.',
                'sort_order'  => 3,
            ],
            [
                'section_id'  => $sigup,
                'title'       => 'How do I mark my shift as complete?',
                'description' => 'After the event, you may be prompted to confirm your attendance. The system or an event coordinator will mark shifts as complete, which logs the hours to your service record.',
                'sort_order'  => 4,
            ],

            // ── Guests ────────────────────────────────────────────────────
            [
                'section_id'  => $guest,
                'title'       => 'What is a guest?',
                'description' => 'A guest is a non-costumed attendee who accompanies a trooper to an event — for example, a family member, partner, or media contact. Guests are listed on the event roster under the trooper who added them.',
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $guest,
                'title'       => 'How do I add a guest to an event?',
                'description' => <<<'MD'
1. Sign up for the shift first (guests can only be added after you're on the roster).
2. On the event page, find your shift entry and click **Add Guest**.
3. Enter the guest's name.
4. The guest will appear on the roster beneath your entry.
MD,
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $guest,
                'title'       => 'Can I remove or update a guest?',
                'description' => 'Yes. Return to the event page, find the guest entry under your shift, and use the update or remove action to make changes.',
                'sort_order'  => 3,
            ],

            // ── Friends ───────────────────────────────────────────────────
            [
                'section_id'  => $frnd,
                'title'       => 'What is the friends feature?',
                'description' => 'The friends feature lets you connect with other troopers on the tracker. Friends are highlighted on event rosters, making it easy to coordinate which events you\'re attending together.',
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $frnd,
                'title'       => 'How do I add a friend?',
                'description' => <<<'MD'
1. Search for a trooper using the global search bar in the navbar.
2. Open their **Service Record** profile page.
3. Use the friend action on their profile to send a friend request.
MD,
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $frnd,
                'title'       => 'How do I manage my friends list?',
                'description' => 'Your friends list is accessible from your account page. You can view current friends and remove connections that are no longer relevant.',
                'sort_order'  => 3,
            ],

            // ── Videos ────────────────────────────────────────────────────
            [
                'section_id' => $vid,
                'title'      => 'Getting Started & Registration',
                'sort_order' => 1,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Setting Up Your Profile',
                'sort_order' => 2,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Adding Your Costumes',
                'sort_order' => 3,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Browsing & Joining Events',
                'sort_order' => 4,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Adding Guests to an Event',
                'sort_order' => 5,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Reading Your Service Record',
                'sort_order' => 6,
            ],
        ];

        foreach ($items as $data)
        {
            Faq::create([
                'section_id'  => $data['section_id'],
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'video_url'   => $data['video_url'] ?? null,
                'sort_order'  => $data['sort_order'],
            ]);
        }
    }
}
