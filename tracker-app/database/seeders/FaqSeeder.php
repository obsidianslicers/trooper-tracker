<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Database\Seeder;

/**
 * The FAQ a new tracker starts with. Command Staff edit it afterwards under Command Staff > FAQ,
 * so it's garrison-written text and isn't translated. When you change an answer here, also
 * update existing trackers with a migration (see 2026_11_25_000001_update_faq_answers.php).
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            ['label' => 'Getting Started & Registration',   'icon' => 'fa-user-plus',       'sort_order' => 1],
            ['label' => 'Account Types',                    'icon' => 'fa-id-card',          'sort_order' => 2],
            ['label' => 'Organizations & Club Memberships', 'icon' => 'fa-sitemap',          'sort_order' => 3],
            ['label' => 'Costumes',                         'icon' => 'fa-shirt',            'sort_order' => 4],
            ['label' => 'Events',                           'icon' => 'fa-calendar',         'sort_order' => 5],
            ['label' => 'Signing Up for Events',            'icon' => 'fa-clipboard-check',  'sort_order' => 6],
            ['label' => 'Guests',                           'icon' => 'fa-user-group',       'sort_order' => 7],
            ['label' => 'Friends',                          'icon' => 'fa-handshake',        'sort_order' => 8],
            ['label' => 'Service Records & Photos',         'icon' => 'fa-medal',            'sort_order' => 9],
            ['label' => 'Notifications & the Mobile App',   'icon' => 'fa-bell',             'sort_order' => 10],
            ['label' => 'Your Account',                     'icon' => 'fa-user-gear',        'sort_order' => 11],
            ['label' => 'How-To Videos',                    'icon' => 'fa-circle-play',      'sort_order' => 12],
        ];

        foreach ($sections as $data) {
            FaqSection::create($data);
        }

        $s = FaqSection::pluck('id', 'label');

        $reg   = $s['Getting Started & Registration'];
        $acct  = $s['Account Types'];
        $orgs  = $s['Organizations & Club Memberships'];
        $cost  = $s['Costumes'];
        $evt   = $s['Events'];
        $sigup = $s['Signing Up for Events'];
        $guest = $s['Guests'];
        $frnd  = $s['Friends'];
        $rec   = $s['Service Records & Photos'];
        $note  = $s['Notifications & the Mobile App'];
        $mine  = $s['Your Account'];
        $vid   = $s['How-To Videos'];

        $items = [

            // ── Registration ──────────────────────────────────────────────
            [
                'section_id'  => $reg,
                'title'       => 'How do I create an account?',
                'description' => <<<'MD'
1. Click **Sign Up** in the navbar.
2. Choose how to sign up: with your **email** and a password, or with one of the logins your garrison offers, such as **Google** or your garrison's forum. Some garrisons turn off email sign-up and ask everyone to use their forum login.
3. Fill in the registration form. The fields are explained below.
4. Select your **Account Type** and the **organizations** you belong to.
5. Press **Register** and wait for **Command Staff approval**.
6. You'll get an email when your account is approved or denied.
MD,
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $reg,
                'title'       => 'What information do I need to register?',
                'description' => <<<'MD'
- **Legal Name**: used for official records and shared with event coordinators for safety reasons. It isn't shown publicly.
- **Display Name**: the name shown on your profile and on event rosters. It can be your first name, a nickname, or anything you like to be called.
- **Email**: used for account emails. It's filled in for you if you sign up with Google or a forum login.
- **Phone**: optional, and only Command Staff can see it.
- **Date of Birth and Guardian Email**: asked only if you're under 18. You must be at least 13, and your guardian must already have an account on the tracker. Your guardian gets an email when you register.
- **Password**: only when you sign up with email.
MD,
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $reg,
                'title'       => 'How long does account approval take?',
                'description' => 'Approval times vary by garrison and Command Staff availability. Most accounts are reviewed within a few days. Until then, logging in shows that your application is under review. You\'ll get an email when your account is approved. If you haven\'t heard back after a week, reach out to your garrison\'s Command Staff directly.',
                'sort_order'  => 3,
            ],
            [
                'section_id'  => $reg,
                'title'       => 'Can I sign up with my existing forum account?',
                'description' => 'Yes, if your garrison has connected the tracker to its forum. You\'ll see a **Sign Up with** button for the forum on the sign-up page. Use it to sign up with your forum login, so you don\'t need a separate password, and use the matching **Login with** button after that.',
                'sort_order'  => 4,
            ],
            [
                'section_id'  => $reg,
                'title'       => 'I need information on joining the 501st Legion.',
                'description' => '[Click here to learn how to join.](https://databank.501st.com/databank/Join_Us)',
                'sort_order'  => 5,
            ],
            [
                'section_id'  => $reg,
                'title'       => 'My registration was denied. What can I do?',
                'description' => 'Log in again. You\'ll see the club requests from your application, and you can update your organization selections and press **Resubmit Application**. A moderator reviews it again and you\'ll get an email with the decision. If you think the denial was a mistake, contact your garrison\'s Command Staff.',
                'sort_order'  => 6,
            ],

            // ── Account Types ─────────────────────────────────────────────
            [
                'section_id'  => $acct,
                'title'       => 'What are the different account types?',
                'description' => <<<'MD'
**Member**: a verified member of one of the listed Star Wars costuming organizations, assigned to a unit. Members are the troopers at an event: they sign up for shifts in a costume and build a service record.

**Handler**: assigned to a unit and helps run an event or takes care of tasks during it, without a costume. Handlers sign up for shifts like members do, but aren't verified members of a costuming organization.

**Visitor**: temporary access for 6 months, for members visiting from elsewhere. Visitors join the top-level organization only, so no region or unit is needed.
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
                'description' => 'Your profile under **Account** shows when your visitor status expires. Once it has expired, logging in takes you to the **Renew Visitor Status** page. Press **Request Renewal** and Command Staff will review it. Renewals are granted at Command Staff\'s discretion, and your access comes back if approved.',
                'sort_order'  => 3,
            ],
            [
                'section_id'  => $acct,
                'title'       => 'What do Event Coordinator, Moderator, Staff Officer and System Owner mean?',
                'description' => <<<'MD'
These are roles Command Staff give to members who help run the tracker:

- **Event Coordinator**: creates events for the organizations they belong to and manages the events they created or were picked to run. They find them under **Events → My Events** and **Create Event**.
- **Moderator**: manages events and members, such as approving accounts, costumes and troops, from the **Command Staff** menu.
- **Staff Officer**: everything in Command Staff except the developer tools (Plugins, System Check and Merge).
- **System Owner**: full access, including the developer tools.

**Service Records → Command Staff** lists your garrison's Command Staff.
MD,
                'sort_order'  => 4,
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
                'description' => <<<'MD'
1. Go to **Account → Memberships**.
2. Under **Organization Join Requests**, pick the organization, then the most specific unit you belong to, and enter your member ID if you have one.
3. Press **Request Access**. A moderator verifies your membership before it's added to your profile.

You can belong to only one unit in each organization. Picking a different unit in the same organization replaces your current one once it's approved. You can view any organization's events without joining it.
MD,
                'sort_order'  => 4,
            ],

            // ── Costumes ──────────────────────────────────────────────────
            [
                'section_id'  => $cost,
                'title'       => 'How do I add a costume to my profile?',
                'description' => <<<'MD'
1. Go to **Account → Costumes**.
2. Under **Find Your Costume**, search for your costume by name (e.g., "Stormtrooper", "Shadow Scout").
3. Select the clubs that approved your costume.
4. Press **Add to Armory**.

The costume shows as **Pending officer review** until an officer of that club approves it, and you'll be notified when they do. Only approved costumes can be picked when you sign up for a troop. If your club's records are synced with the tracker, the costumes it lists for you are added and approved automatically.
MD,
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $cost,
                'title'       => 'Can I have more than one costume?',
                'description' => 'Absolutely. Add as many costumes as you own. When you sign up for a shift you pick the costume you\'ll wear, and you can also pick a backup costume in case your main one can\'t make it.',
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $cost,
                'title'       => 'How do I remove a costume from my profile?',
                'description' => 'Go to **Account → Costumes**, find the costume in your list, and remove it. Your past troops in that costume keep their credit. If you add it again later, it comes back the way it was.',
                'sort_order'  => 3,
            ],
            [
                'section_id'  => $cost,
                'title'       => 'My costume isn\'t in the list — what do I do?',
                'description' => 'Costumes are managed by Command Staff. Ask your unit leader or contact Command Staff to have it added to the tracker.',
                'sort_order'  => 4,
            ],
            [
                'section_id'  => $cost,
                'title'       => 'Why is my costume pending, or why did it disappear?',
                'description' => <<<'MD'
A costume you add yourself waits for an officer of that club to approve it. Until then you can't sign up in it.

An officer can also **revoke** a costume, for example after it was sold. A revoked costume leaves your profile and can't be used for new sign-ups, but your past troops in it keep their credit. Adding it again sends it back to an officer for review. If you think a costume was denied or revoked by mistake, contact your unit leader.
MD,
                'sort_order'  => 6,
            ],

            // ── Events ────────────────────────────────────────────────────
            [
                'section_id'  => $evt,
                'title'       => 'How do I find upcoming events?',
                'description' => <<<'MD'
Click **Events** in the navbar. You can view them as:

- **List**: upcoming events, which you can search by event name or venue
- **Calendar**: a monthly calendar of events
- **Map**: an interactive map of event locations

Use **Filter by squad** to see only the squads you troop with. **Completed** and **Cancelled** show past events.
MD,
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $evt,
                'title'       => 'What information is shown on an event page?',
                'description' => <<<'MD'
- Event name, date, venue and map
- Who is hosting it and which clubs can troop
- The shifts, with how many spots are left, and the roster for each: who is going and in what costume
- The **Mission Brief** with important event-specific information
- Whether you're signed up, near the top of the page
- **Add to Calendar** and **Watch Event** buttons
- Photos: instructional uploads (maps, parking, directions) and troop photos
MD,
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $evt,
                'title'       => 'What is a shift?',
                'description' => 'A shift is a specific time slot within an event. Some events have a single shift covering the whole appearance; others split into multiple shifts (e.g., morning and afternoon). You sign up for the shift(s) you can attend, not the event as a whole. Some shifts also have stations, and you pick yours when you sign up.',
                'sort_order'  => 3,
            ],
            [
                'section_id'  => $evt,
                'title'       => 'What is a standby list?',
                'description' => <<<'MD'
Each shift has a limited number of spots, and spots go in sign-up order. If a shift is full when you sign up, you join the **stand-by** list and the page tells you your place in line. When someone who is going cancels, the next trooper on stand-by moves up automatically and is notified.

For some events, Command Staff pick who goes from the sign-ups. Everyone who signs up for those is on stand-by until they're picked.
MD,
                'sort_order'  => 4,
            ],
            [
                'section_id'  => $evt,
                'title'       => 'What does Watch Event do?',
                'description' => 'Press **Watch Event** on an event page to get notifications when the event is updated or troopers sign up and cancel, even if you\'re not signed up yourself. Press it again to stop watching.',
                'sort_order'  => 5,
            ],
            [
                'section_id'  => $evt,
                'title'       => 'How do I add an event to my calendar?',
                'description' => 'Press **Add to Calendar** on the event page and choose Google, Outlook, Yahoo, or an .ics file for Apple Calendar and other apps. You can add the whole event or a single shift.',
                'sort_order'  => 6,
            ],
            [
                'section_id'  => $evt,
                'title'       => 'Do all events count toward my troop totals?',
                'description' => 'Most do. Some event types don\'t, and their event page says **Does not count toward troop totals**. Your garrison may also count shifts of the same event on the same day as one troop; the event page tells you when that applies. Your hours and charity still count for every shift.',
                'sort_order'  => 7,
            ],

            // ── Signing Up ────────────────────────────────────────────────
            [
                'section_id'  => $sigup,
                'title'       => 'How do I sign up for an event shift?',
                'description' => <<<'MD'
1. Open the event page. If it has a **Mission Brief** you must confirm, read it and confirm you've read it. The **Sign Up** buttons appear after you do.
2. Press **Sign Up** on the shift you want. Working more than one shift? Sign up for each one.
3. Check your **costume** (the one you wore last time is already picked), or choose **Attending without a costume**. Handlers and events that don't need costumes skip this step.
4. Pick a **station** if the shift has them, and a backup costume if you like.
5. Press **Confirm**. You're on the roster, or on stand-by if the shift is full.
MD,
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $sigup,
                'title'       => 'Can I update my signup after the fact?',
                'description' => 'Yes. Once you\'re signed up, your row on the shift\'s roster is your sign-up. Press **Edit** on it to change your costume, backup costume, or switch between **Going** and **Tentative**, then press **Done**. Each change saves as soon as you pick it.',
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $sigup,
                'title'       => 'How do I withdraw from a shift?',
                'description' => 'Press **Edit** on your row on the shift\'s roster, then **Cancel my sign-up**. The next trooper on stand-by moves up automatically. If you\'re withdrawing close to the event date, please also let the event coordinator know.',
                'sort_order'  => 3,
            ],
            [
                'section_id'  => $sigup,
                'title'       => 'How do I mark my shift as complete?',
                'description' => <<<'MD'
After the shift, the tracker asks **Did you make it?** by email and in the notifications bell. Press **Made It** or **Missed It**.

- If your costume is approved by more than one of your clubs, you choose which clubs get credit for the troop.
- Some troops go to a moderator to verify before they count, and show as **Awaiting Verification** until then.

Your hours and charity are then added to your service record. If the event closed a while ago and you can no longer answer, contact Command Staff.
MD,
                'sort_order'  => 4,
            ],
            [
                'section_id'  => $sigup,
                'title'       => 'How do I know I confirmed a troop?',
                'description' => 'The troop will be listed on your Service Record, or under your **Troops/Missions** stat. When you confirm a troop, your status will change from **Going** to **Attended**.',
                'sort_order'  => 5,
            ],
            [
                'section_id'  => $sigup,
                'title'       => 'I am missing troop data / My troop data is incorrect.',
                'description' => 'Please refer to your unit leader to get this corrected.',
                'sort_order'  => 6,
            ],

            // ── Guests ────────────────────────────────────────────────────
            [
                'section_id'  => $guest,
                'title'       => 'What is a guest?',
                'description' => 'A guest is someone without a tracker account who comes with you to an event, for example a family member, partner, or media contact. Guests are listed on the event roster. To bring someone who has a tracker account, add them as a friend instead.',
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $guest,
                'title'       => 'How do I add a guest to an event?',
                'description' => <<<'MD'
1. Sign up for the shift first. You can add guests once you're going.
2. If the event allows guests, press **Add a guest** under the shift.
3. Enter your guest's name and save.
4. Your guest appears on the roster with their own **Edit** button.
MD,
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $guest,
                'title'       => 'Can I remove or update a guest?',
                'description' => 'Yes. Press **Edit** on your guest\'s row on the shift\'s roster to change their details or cancel their spot.',
                'sort_order'  => 3,
            ],

            // ── Friends ───────────────────────────────────────────────────
            [
                'section_id'  => $frnd,
                'title'       => 'What is the friends feature?',
                'description' => 'Friends are troopers with a tracker account that you sign up for a shift with **Add a friend**. Signing someone up links the two of you as friends, so they\'re easy to find the next time you add them.',
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $frnd,
                'title'       => 'How do I add a friend?',
                'description' => <<<'MD'
1. Sign up for the shift first. You can add friends once you're going.
2. If the event allows it, press **Add a friend** under the shift.
3. Pick the trooper. Your friends are listed first, or search for anyone else.
4. Pick their costume and press **Confirm**. They're notified that they were signed up.
MD,
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $frnd,
                'title'       => 'How do I manage my friends list?',
                'description' => 'Your friends are listed under **Account → Friends** once you have any. The list is built from the troopers you\'ve signed up with **Add a friend**.',
                'sort_order'  => 3,
            ],
            [
                'section_id'  => $frnd,
                'title'       => 'How do I find another trooper?',
                'description' => 'Use the search box in the navbar. It finds troopers, events and member IDs. Open a trooper to see their **Service Record**.',
                'sort_order'  => 4,
            ],

            // ── Service Records & Photos ──────────────────────────────────
            [
                'section_id'  => $rec,
                'title'       => 'What is my Service Record?',
                'description' => <<<'MD'
**Service Records → My Service** shows your troops, hours, charity raised, costumes, awards and achievements. Every trooper has one, and you can open anyone's from the search box or an event roster.

The **Service Records** menu also has the **Leaderboard**, **Awards**, **Achievements**, the **Photo Gallery**, and your garrison's **Command Staff**.
MD,
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $rec,
                'title'       => 'I troop with another garrison\'s tracker too. Can I see all my troops in one place?',
                'description' => <<<'MD'
Yes, if your garrison has turned on the **HoloNet**:

1. Go to **Account** and press **Open the HoloNet**.
2. Press **Link a Tracker** and choose the other garrison's tracker.
3. Sign in to that tracker and confirm the link there.

Troops from linked trackers show on your service record and in your all-time totals. They don't count toward this tracker's reports, awards or club credit. The other tracker sees only your troops (event, date, costume and clubs credited), never your email, phone or legal name, and you can unlink or remove its access at any time. Troopers under 18 can't link trackers.
MD,
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $rec,
                'title'       => 'How do I upload photos from an event?',
                'description' => <<<'MD'
Open the event page and drag your photos onto the upload box, or click it to choose them. JPG, PNG, WEBP and HEIC images work.

Use **Upload as** to choose where they go:

- **Instructional**: maps, parking and directions, shown before the troop
- **Troop photo**: pictures from the troop, shown with the other troop photos and in the Photo Gallery

It starts on Instructional before the event and Troop photo after it begins. Put a photo in the wrong place? Use **Move to Mission Review** or **Move to Instructional** on it. You can move your own photos; event coordinators can move any.
MD,
                'sort_order'  => 3,
            ],
            [
                'section_id'  => $rec,
                'title'       => 'How do I tag myself in a photo?',
                'description' => 'Find the photo on its event page (the **Photo Gallery** links to it) and press **Tag me**. Tagged photos show on the Photos tab of your service record. Press **Untag me** to remove your tag.',
                'sort_order'  => 4,
            ],

            // ── Notifications & the Mobile App ────────────────────────────
            [
                'section_id'  => $note,
                'title'       => 'How do I choose which notifications I get?',
                'description' => <<<'MD'
Go to **Account → Notifications**:

- **Organization Event Notifications**: which clubs, garrisons and squads you hear about when new events are created.
- **Notification Frequency**: how often you get emails about new events.
- **Notification Preferences**: for each kind of notification (event created, updated or cancelled, sign-ups, completed shifts and more), whether it comes by email, push to the mobile app, or the website.

Website notifications appear under the bell in the navbar.
MD,
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $note,
                'title'       => 'Is there a mobile app?',
                'description' => <<<'MD'
Yes. **Troop Tracker** is on the [App Store](https://apps.apple.com/us/app/troop-tracker/id6739888656) and [Google Play](https://play.google.com/store/apps/details?id=com.drennansoftware.troop_tracker). It's set up for the Florida Garrison only for now.

The app shows the tracker and adds push notifications, a notification inbox, and an unread count on the app icon. To get push alerts, turn on **Allow Push Notifications** under **Account → Notifications**.
MD,
                'sort_order'  => 2,
            ],

            // ── Your Account ──────────────────────────────────────────────
            [
                'section_id'  => $mine,
                'title'       => 'How do I change my name, phone, theme or language?',
                'description' => 'Go to **Account → Profile**, change what you need, and press **Save**. If your garrison offers more than one language, choose yours under **Language**; it\'s used for the tracker and your emails. Text your garrison writes, such as events, notices and this FAQ, stays in the language it was written in.',
                'sort_order'  => 1,
            ],
            [
                'section_id'  => $mine,
                'title'       => 'I\'m a guardian. Where do I see my cadet\'s account?',
                'description' => 'When a trooper under 18 registers with your email as their guardian, you get an email and their account is listed under **Account → Cadets**.',
                'sort_order'  => 2,
            ],
            [
                'section_id'  => $mine,
                'title'       => 'How do I delete my account?',
                'description' => <<<'MD'
Go to **Account → Delete Account**, type **DELETE** to confirm, and submit. Your account is deleted after 30 days, and you can cancel any time before then by logging back in.

Your name, email, phone and login are removed. Your event participation is kept in anonymized form.
MD,
                'sort_order'  => 3,
            ],

            // ── Videos ────────────────────────────────────────────────────
            [
                'section_id' => $vid,
                'title'      => 'Getting Started & Registration',
                'video_url'  => 'https://www.youtube.com/watch?v=JPkDomoU86s',
                'sort_order' => 1,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Setting Up Your Profile',
                'video_url'  => 'https://youtu.be/2Yfe1gDFs68',
                'sort_order' => 2,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Adding Your Costumes',
                'video_url'  => 'https://youtu.be/dZH80acyRvg',
                'sort_order' => 3,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Browsing & Joining Events',
                'video_url'  => 'https://youtu.be/HIFjIoTPnTc',
                'sort_order' => 4,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Reading Your Service Record',
                'video_url'  => 'https://youtu.be/0TnnUMDEejA',
                'sort_order' => 5,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Adding An Organization',
                'video_url'  => 'https://youtu.be/dkliWoLKrq0',
                'sort_order' => 6,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Command Staff - Overview',
                'video_url'  => 'https://youtu.be/DJcNoX0rQ5o',
                'sort_order' => 7,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Command Staff - Create & Manage Events',
                'video_url'  => 'https://youtu.be/9221Iqdo35Q',
                'sort_order' => 8,
            ],
            [
                'section_id' => $vid,
                'title'      => 'Command Staff - Trooper Requests',
                'video_url'  => 'https://youtu.be/tZpetAW2ob4',
                'sort_order' => 9,
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
