<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Brings the FAQ that FaqSeeder wrote up to date with how the tracker works now, and adds
 * answers for newer features.
 *
 * The FAQ belongs to each garrison, so only answers still as seeded are changed: an answer
 * Command Staff edited keeps their text, and a question they already have isn't added twice.
 * A tracker whose FAQ didn't come from the seeder is left alone.
 */
return new class extends Migration {
    private const SEEDED_SECTION = 'Getting Started & Registration';

    private const VIDEOS_SECTION = 'How-To Videos';

    /** New sections, in order, placed before the videos. */
    private const SECTIONS = [
        ['Service Records & Photos', 'fa-medal'],
        ['Notifications & the Mobile App', 'fa-bell'],
        ['Your Account', 'fa-user-gear'],
    ];

    /** Answers rewritten; "was" is the md5 of the seeded answer they replace. */
    private const CHANGED = [
        [
            'section' => 'Getting Started & Registration',
            'title' => 'How do I create an account?',
            'was' => '0359519fe8da74547a02fbe3392ba5ef',
            'description' => <<<'MD'
1. Click **Sign Up** in the navbar.
2. Choose how to sign up: with your **email** and a password, or with one of the logins your garrison offers, such as **Google** or your garrison's forum. Some garrisons turn off email sign-up and ask everyone to use their forum login.
3. Fill in the registration form. The fields are explained below.
4. Select your **Account Type** and the **organizations** you belong to.
5. Press **Register** and wait for **Command Staff approval**.
6. You'll get an email when your account is approved or denied.
MD,
        ],
        [
            'section' => 'Getting Started & Registration',
            'title' => 'What information do I need to register?',
            'was' => 'f7d749314ac318e01e91e98be67b22b9',
            'description' => <<<'MD'
- **Legal Name**: used for official records and shared with event coordinators for safety reasons. It isn't shown publicly.
- **Display Name**: the name shown on your profile and on event rosters. It can be your first name, a nickname, or anything you like to be called.
- **Email**: used for account emails. It's filled in for you if you sign up with Google or a forum login.
- **Phone**: optional, and only Command Staff can see it.
- **Date of Birth and Guardian Email**: asked only if you're under 18. You must be at least 13, and your guardian must already have an account on the tracker. Your guardian gets an email when you register.
- **Password**: only when you sign up with email.
MD,
        ],
        [
            'section' => 'Getting Started & Registration',
            'title' => 'How long does account approval take?',
            'was' => '84ee767887a1db02ad214139ca1454f5',
            'description' => 'Approval times vary by garrison and Command Staff availability. Most accounts are reviewed within a few days. Until then, logging in shows that your application is under review. You\'ll get an email when your account is approved. If you haven\'t heard back after a week, reach out to your garrison\'s Command Staff directly.',
        ],
        [
            'section' => 'Getting Started & Registration',
            'title' => 'Can I sign up with my existing forum account?',
            'was' => '20b297f771af762dc60fc86ea85bcc00',
            'description' => 'Yes, if your garrison has connected the tracker to its forum. You\'ll see a **Sign Up with** button for the forum on the sign-up page. Use it to sign up with your forum login, so you don\'t need a separate password, and use the matching **Login with** button after that.',
        ],
        [
            'section' => 'Account Types',
            'title' => 'What are the different account types?',
            'was' => '5f95f56f85e7385d13301c97573bea9e',
            'description' => <<<'MD'
**Member**: a verified member of one of the listed Star Wars costuming organizations, assigned to a unit. Members are the troopers at an event: they sign up for shifts in a costume and build a service record.

**Handler**: assigned to a unit and helps run an event or takes care of tasks during it, without a costume. Handlers sign up for shifts like members do, but aren't verified members of a costuming organization.

**Visitor**: temporary access for 6 months, for members visiting from elsewhere. Visitors join the top-level organization only, so no region or unit is needed.
MD,
        ],
        [
            'section' => 'Account Types',
            'title' => 'My Visitor access is expiring — how do I renew it?',
            'was' => 'c1dc6dd4a06836187e363269b777cf39',
            'description' => 'Your profile under **Account** shows when your visitor status expires. Once it has expired, logging in takes you to the **Renew Visitor Status** page. Press **Request Renewal** and Command Staff will review it. Renewals are granted at Command Staff\'s discretion, and your access comes back if approved.',
        ],
        [
            'section' => 'Organizations & Club Memberships',
            'title' => 'How do I update my club memberships after registration?',
            'was' => '712d47887b0b575610b73c60df93674e',
            'description' => <<<'MD'
1. Go to **Account → Memberships**.
2. Under **Organization Join Requests**, pick the organization, then the most specific unit you belong to, and enter your member ID if you have one.
3. Press **Request Access**. A moderator verifies your membership before it's added to your profile.

You can belong to only one unit in each organization. Picking a different unit in the same organization replaces your current one once it's approved. You can view any organization's events without joining it.
MD,
        ],
        [
            'section' => 'Costumes',
            'title' => 'How do I add a costume to my profile?',
            'was' => 'f9b0a8e10f341611b0fb7ed0e7ce6048',
            'description' => <<<'MD'
1. Go to **Account → Costumes**.
2. Under **Find Your Costume**, search for your costume by name (e.g., "Stormtrooper", "Shadow Scout").
3. Select the clubs that approved your costume.
4. Press **Add to Armory**.

The costume shows as **Pending officer review** until an officer of that club approves it, and you'll be notified when they do. Only approved costumes can be picked when you sign up for a troop. If your club's records are synced with the tracker, the costumes it lists for you are added and approved automatically.
MD,
        ],
        [
            'section' => 'Costumes',
            'title' => 'Can I have more than one costume?',
            'was' => 'a3d78d884596536585670a88d29284cf',
            'description' => 'Absolutely. Add as many costumes as you own. When you sign up for a shift you pick the costume you\'ll wear, and you can also pick a backup costume in case your main one can\'t make it.',
        ],
        [
            'section' => 'Costumes',
            'title' => 'How do I remove a costume from my profile?',
            'was' => '5c77127564d4e6364f9382705341f0ca',
            'description' => 'Go to **Account → Costumes**, find the costume in your list, and remove it. Your past troops in that costume keep their credit. If you add it again later, it comes back the way it was.',
        ],
        [
            'section' => 'Costumes',
            'title' => 'My costume isn\'t in the list — what do I do?',
            'was' => 'f41d0a80a3d816efb26e0d793d186104',
            'description' => 'Costumes are managed by Command Staff. Ask your unit leader or contact Command Staff to have it added to the tracker.',
        ],
        [
            'section' => 'Events',
            'title' => 'How do I find upcoming events?',
            'was' => 'a738e1d95d99d92b61e0a84bcb02f524',
            'description' => <<<'MD'
Click **Events** in the navbar. You can view them as:

- **List**: upcoming events, which you can search by event name or venue
- **Calendar**: a monthly calendar of events
- **Map**: an interactive map of event locations

Use **Filter by squad** to see only the squads you troop with. **Completed** and **Cancelled** show past events.
MD,
        ],
        [
            'section' => 'Events',
            'title' => 'What information is shown on an event page?',
            'was' => '386af2c7004f473ffa18e066df3d1b78',
            'description' => <<<'MD'
- Event name, date, venue and map
- Who is hosting it and which clubs can troop
- The shifts, with how many spots are left, and the roster for each: who is going and in what costume
- The **Mission Brief** with important event-specific information
- Whether you're signed up, near the top of the page
- **Add to Calendar** and **Watch Event** buttons
- Photos: instructional uploads (maps, parking, directions) and troop photos
MD,
        ],
        [
            'section' => 'Events',
            'title' => 'What is a shift?',
            'was' => '355381bd4f2683e6392c77275c4224d3',
            'description' => 'A shift is a specific time slot within an event. Some events have a single shift covering the whole appearance; others split into multiple shifts (e.g., morning and afternoon). You sign up for the shift(s) you can attend, not the event as a whole. Some shifts also have stations, and you pick yours when you sign up.',
        ],
        [
            'section' => 'Events',
            'title' => 'What is a standby list?',
            'was' => 'd85ca3f706edcd6961b366cd267587ba',
            'description' => <<<'MD'
Each shift has a limited number of spots, and spots go in sign-up order. If a shift is full when you sign up, you join the **stand-by** list and the page tells you your place in line. When someone who is going cancels, the next trooper on stand-by moves up automatically and is notified.

For some events, Command Staff pick who goes from the sign-ups. Everyone who signs up for those is on stand-by until they're picked.
MD,
        ],
        [
            'section' => 'Signing Up for Events',
            'title' => 'How do I sign up for an event shift?',
            'was' => '8c0305b1e086c2441594015692e8c641',
            'description' => <<<'MD'
1. Open the event page. If it has a **Mission Brief** you must confirm, read it and confirm you've read it. The **Sign Up** buttons appear after you do.
2. Press **Sign Up** on the shift you want. Working more than one shift? Sign up for each one.
3. Check your **costume** (the one you wore last time is already picked), or choose **Attending without a costume**. Handlers and events that don't need costumes skip this step.
4. Pick a **station** if the shift has them, and a backup costume if you like.
5. Press **Confirm**. You're on the roster, or on stand-by if the shift is full.
MD,
        ],
        [
            'section' => 'Signing Up for Events',
            'title' => 'Can I update my signup after the fact?',
            'was' => '7be60b43f3f9f700301a74f7a859ca26',
            'description' => 'Yes. Once you\'re signed up, your row on the shift\'s roster is your sign-up. Press **Edit** on it to change your costume, backup costume, or switch between **Going** and **Tentative**, then press **Done**. Each change saves as soon as you pick it.',
        ],
        [
            'section' => 'Signing Up for Events',
            'title' => 'How do I withdraw from a shift?',
            'was' => 'dda290adddfaa6faa378d78684ef788a',
            'description' => 'Press **Edit** on your row on the shift\'s roster, then **Cancel my sign-up**. The next trooper on stand-by moves up automatically. If you\'re withdrawing close to the event date, please also let the event coordinator know.',
        ],
        [
            'section' => 'Signing Up for Events',
            'title' => 'How do I mark my shift as complete?',
            'was' => '8c6c11f6241269f7098352b3e2d4c49a',
            'description' => <<<'MD'
After the shift, the tracker asks **Did you make it?** by email and in the notifications bell. Press **Made It** or **Missed It**.

- If your costume is approved by more than one of your clubs, you choose which clubs get credit for the troop.
- Some troops go to a moderator to verify before they count, and show as **Awaiting Verification** until then.

Your hours and charity are then added to your service record. If the event closed a while ago and you can no longer answer, contact Command Staff.
MD,
        ],
        [
            'section' => 'Guests',
            'title' => 'What is a guest?',
            'was' => '87cf016cd7d0156993d15543fcfd4cdd',
            'description' => 'A guest is someone without a tracker account who comes with you to an event, for example a family member, partner, or media contact. Guests are listed on the event roster. To bring someone who has a tracker account, add them as a friend instead.',
        ],
        [
            'section' => 'Guests',
            'title' => 'How do I add a guest to an event?',
            'was' => 'a185001ccd2bbab31e838637a1c5dce7',
            'description' => <<<'MD'
1. Sign up for the shift first. You can add guests once you're going.
2. If the event allows guests, press **Add a guest** under the shift.
3. Enter your guest's name and save.
4. Your guest appears on the roster with their own **Edit** button.
MD,
        ],
        [
            'section' => 'Guests',
            'title' => 'Can I remove or update a guest?',
            'was' => '2270d17240adb488b875ab715db67b75',
            'description' => 'Yes. Press **Edit** on your guest\'s row on the shift\'s roster to change their details or cancel their spot.',
        ],
        [
            'section' => 'Friends',
            'title' => 'What is the friends feature?',
            'was' => '09a6c84c4fdd048879e8b4d20d2ab810',
            'description' => 'Friends are troopers with a tracker account that you sign up for a shift with **Add a friend**. Signing someone up links the two of you as friends, so they\'re easy to find the next time you add them.',
        ],
        [
            'section' => 'Friends',
            'title' => 'How do I add a friend?',
            'was' => 'c0dbe1e99d48fec2ce56b9d4f24c0d4c',
            'description' => <<<'MD'
1. Sign up for the shift first. You can add friends once you're going.
2. If the event allows it, press **Add a friend** under the shift.
3. Pick the trooper. Your friends are listed first, or search for anyone else.
4. Pick their costume and press **Confirm**. They're notified that they were signed up.
MD,
        ],
        [
            'section' => 'Friends',
            'title' => 'How do I manage my friends list?',
            'was' => '1aee8bddca4ea4b521133d2350abd151',
            'description' => 'Your friends are listed under **Account → Friends** once you have any. The list is built from the troopers you\'ve signed up with **Add a friend**.',
        ],
    ];

    private const ADDED = [
        [
            'section' => 'Getting Started & Registration',
            'title' => 'My registration was denied. What can I do?',
            'sort_order' => 6,
            'description' => 'Log in again. You\'ll see the club requests from your application, and you can update your organization selections and press **Resubmit Application**. A moderator reviews it again and you\'ll get an email with the decision. If you think the denial was a mistake, contact your garrison\'s Command Staff.',
        ],
        [
            'section' => 'Account Types',
            'title' => 'What do Event Coordinator, Moderator, Staff Officer and System Owner mean?',
            'sort_order' => 4,
            'description' => <<<'MD'
These are roles Command Staff give to members who help run the tracker:

- **Event Coordinator**: creates events for the organizations they belong to and manages the events they created or were picked to run. They find them under **Events → My Events** and **Create Event**.
- **Moderator**: manages events and members, such as approving accounts, costumes and troops, from the **Command Staff** menu.
- **Staff Officer**: everything in Command Staff except the developer tools (Plugins, System Check and Merge).
- **System Owner**: full access, including the developer tools.

**Service Records → Command Staff** lists your garrison's Command Staff.
MD,
        ],
        [
            'section' => 'Costumes',
            'title' => 'Why is my costume pending, or why did it disappear?',
            'sort_order' => 6,
            'description' => <<<'MD'
A costume you add yourself waits for an officer of that club to approve it. Until then you can't sign up in it.

An officer can also **revoke** a costume, for example after it was sold. A revoked costume leaves your profile and can't be used for new sign-ups, but your past troops in it keep their credit. Adding it again sends it back to an officer for review. If you think a costume was denied or revoked by mistake, contact your unit leader.
MD,
        ],
        [
            'section' => 'Events',
            'title' => 'What does Watch Event do?',
            'sort_order' => 5,
            'description' => 'Press **Watch Event** on an event page to get notifications when the event is updated or troopers sign up and cancel, even if you\'re not signed up yourself. Press it again to stop watching.',
        ],
        [
            'section' => 'Events',
            'title' => 'How do I add an event to my calendar?',
            'sort_order' => 6,
            'description' => 'Press **Add to Calendar** on the event page and choose Google, Outlook, Yahoo, or an .ics file for Apple Calendar and other apps. You can add the whole event or a single shift.',
        ],
        [
            'section' => 'Events',
            'title' => 'Do all events count toward my troop totals?',
            'sort_order' => 7,
            'description' => 'Most do. Some event types don\'t, and their event page says **Does not count toward troop totals**. Your garrison may also count shifts of the same event on the same day as one troop; the event page tells you when that applies. Your hours and charity still count for every shift.',
        ],
        [
            'section' => 'Friends',
            'title' => 'How do I find another trooper?',
            'sort_order' => 4,
            'description' => 'Use the search box in the navbar. It finds troopers, events and member IDs. Open a trooper to see their **Service Record**.',
        ],
        [
            'section' => 'Service Records & Photos',
            'title' => 'What is my Service Record?',
            'sort_order' => 1,
            'description' => <<<'MD'
**Service Records → My Service** shows your troops, hours, charity raised, costumes, awards and achievements. Every trooper has one, and you can open anyone's from the search box or an event roster.

The **Service Records** menu also has the **Leaderboard**, **Awards**, **Achievements**, the **Photo Gallery**, and your garrison's **Command Staff**.
MD,
        ],
        [
            'section' => 'Service Records & Photos',
            'title' => 'I troop with another garrison\'s tracker too. Can I see all my troops in one place?',
            'sort_order' => 2,
            'description' => <<<'MD'
Yes, if your garrison has turned on the **HoloNet**:

1. Go to **Account** and press **Open the HoloNet**.
2. Press **Link a Tracker** and choose the other garrison's tracker.
3. Sign in to that tracker and confirm the link there.

Troops from linked trackers show on your service record and in your all-time totals. They don't count toward this tracker's reports, awards or club credit. The other tracker sees only your troops (event, date, costume and clubs credited), never your email, phone or legal name, and you can unlink or remove its access at any time. Troopers under 18 can't link trackers.
MD,
        ],
        [
            'section' => 'Service Records & Photos',
            'title' => 'How do I upload photos from an event?',
            'sort_order' => 3,
            'description' => <<<'MD'
Open the event page and drag your photos onto the upload box, or click it to choose them. JPG, PNG, WEBP and HEIC images work.

Use **Upload as** to choose where they go:

- **Instructional**: maps, parking and directions, shown before the troop
- **Troop photo**: pictures from the troop, shown with the other troop photos and in the Photo Gallery

It starts on Instructional before the event and Troop photo after it begins. Put a photo in the wrong place? Use **Move to Mission Review** or **Move to Instructional** on it. You can move your own photos; event coordinators can move any.
MD,
        ],
        [
            'section' => 'Service Records & Photos',
            'title' => 'How do I tag myself in a photo?',
            'sort_order' => 4,
            'description' => 'Find the photo on its event page (the **Photo Gallery** links to it) and press **Tag me**. Tagged photos show on the Photos tab of your service record. Press **Untag me** to remove your tag.',
        ],
        [
            'section' => 'Notifications & the Mobile App',
            'title' => 'How do I choose which notifications I get?',
            'sort_order' => 1,
            'description' => <<<'MD'
Go to **Account → Notifications**:

- **Organization Event Notifications**: which clubs, garrisons and squads you hear about when new events are created.
- **Notification Frequency**: how often you get emails about new events.
- **Notification Preferences**: for each kind of notification (event created, updated or cancelled, sign-ups, completed shifts and more), whether it comes by email, push to the mobile app, or the website.

Website notifications appear under the bell in the navbar.
MD,
        ],
        [
            'section' => 'Notifications & the Mobile App',
            'title' => 'Is there a mobile app?',
            'sort_order' => 2,
            'description' => <<<'MD'
Yes. **Troop Tracker** is on the [App Store](https://apps.apple.com/us/app/troop-tracker/id6739888656) and [Google Play](https://play.google.com/store/apps/details?id=com.drennansoftware.troop_tracker). It's set up for the Florida Garrison only for now.

The app shows the tracker and adds push notifications, a notification inbox, and an unread count on the app icon. To get push alerts, turn on **Allow Push Notifications** under **Account → Notifications**.
MD,
        ],
        [
            'section' => 'Your Account',
            'title' => 'How do I change my name, phone, theme or language?',
            'sort_order' => 1,
            'description' => 'Go to **Account → Profile**, change what you need, and press **Save**. If your garrison offers more than one language, choose yours under **Language**; it\'s used for the tracker and your emails. Text your garrison writes, such as events, notices and this FAQ, stays in the language it was written in.',
        ],
        [
            'section' => 'Your Account',
            'title' => 'I\'m a guardian. Where do I see my cadet\'s account?',
            'sort_order' => 2,
            'description' => 'When a trooper under 18 registers with your email as their guardian, you get an email and their account is listed under **Account → Cadets**.',
        ],
        [
            'section' => 'Your Account',
            'title' => 'How do I delete my account?',
            'sort_order' => 3,
            'description' => <<<'MD'
Go to **Account → Delete Account**, type **DELETE** to confirm, and submit. Your account is deleted after 30 days, and you can cancel any time before then by logging back in.

Your name, email, phone and login are removed. Your event participation is kept in anonymized form.
MD,
        ],
    ];

    /** Questions dropped because another answer now covers them. */
    private const REMOVED = [
        [
            'section' => 'Costumes',
            'title' => 'I need a costume added to the Troop Tracker.',
            'was' => '6c6587d4edbc6c9c6a5924924db70f6e',
        ],
    ];

    public function up(): void
    {
        if ($this->sectionId(self::SEEDED_SECTION) === null)
        {
            return;
        }

        $this->addSections();

        foreach (self::CHANGED as $item)
        {
            $this->seededItem($item)?->update([
                'description' => $item['description'],
                'updated_at' => now(),
            ]);
        }

        foreach (self::REMOVED as $item)
        {
            $this->seededItem($item)?->update(['deleted_at' => now()]);
        }

        foreach (self::ADDED as $item)
        {
            $this->addItem($item);
        }
    }

    /**
     * The old answers described screens that no longer exist, so there is nothing to restore.
     */
    public function down(): void
    {
    }

    private function addSections(): void
    {
        $videos_id = $this->sectionId(self::VIDEOS_SECTION);
        $sort_order = $videos_id === null
            ? (int) DB::table('tt_faq_sections')->max('sort_order') + 1
            : (int) DB::table('tt_faq_sections')->where('id', $videos_id)->value('sort_order');

        foreach (self::SECTIONS as [$label, $icon])
        {
            if ($this->sectionId($label) === null)
            {
                DB::table('tt_faq_sections')->insert([
                    'label' => $label,
                    'icon' => $icon,
                    'sort_order' => $sort_order++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if ($videos_id !== null)
        {
            DB::table('tt_faq_sections')
                ->where('id', $videos_id)
                ->update(['sort_order' => $sort_order]);
        }
    }

    /**
     * @param  array{section: string, title: string, sort_order: int, description: string}  $item
     */
    private function addItem(array $item): void
    {
        $section_id = $this->sectionId($item['section']);

        if ($section_id === null || $this->items($section_id, $item['title'])->exists())
        {
            return;
        }

        DB::table('tt_faq')->insert([
            'section_id' => $section_id,
            'title' => $item['title'],
            'description' => $item['description'],
            'sort_order' => $item['sort_order'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * The question, when its answer is still the one the seeder wrote.
     *
     * @param  array{section: string, title: string, was: string}  $item
     */
    private function seededItem(array $item): ?Builder
    {
        $section_id = $this->sectionId($item['section']);

        if ($section_id === null)
        {
            return null;
        }

        $row = $this->items($section_id, $item['title'])->first();

        if ($row === null || $this->fingerprint($row->description) !== $item['was'])
        {
            return null;
        }

        return DB::table('tt_faq')->where('id', $row->id);
    }

    private function fingerprint(?string $description): string
    {
        return md5(trim(str_replace("\r\n", "\n", (string) $description)));
    }

    private function items(int $section_id, string $title): Builder
    {
        return DB::table('tt_faq')
            ->where('section_id', $section_id)
            ->where('title', $title)
            ->whereNull('deleted_at');
    }

    private function sectionId(string $label): ?int
    {
        $id = DB::table('tt_faq_sections')
            ->where('label', $label)
            ->whereNull('deleted_at')
            ->value('id');

        return $id === null ? null : (int) $id;
    }
};
