# Browser Tests (Playwright)

The browser tests drive the real app in Chromium: Blade, Svelte/Inertia, Alpine and HTMX
together, on a throwaway SQLite database seeded with known personas. PHPUnit stays the place
for business rules; these tests check that pages render and that the key flows work end to end.

## Running them

From `tracker-app/`:

```bash
npm ci
npm run build                       # the tests use the built assets (or keep `npm run dev` running)
npx playwright install chromium     # once per machine
npm run test:e2e                    # the whole suite
npx playwright test approvals       # one spec
npx playwright test --ui            # watch mode with a browser view
npx playwright show-report          # last HTML report
```

Playwright starts the app itself (`tests/e2e/support/server.js`): it rebuilds
`database/e2e.sqlite` from the migrations and `Database\Seeders\E2eSeeder`, then serves on
`http://127.0.0.1:8077` (set `E2E_PORT` to change it). Its environment overrides your `.env`,
so your local database is never touched. Set `PHP_BINARY` if `php` on your path is not PHP 8.4.

CI runs the suite in the `e2e-tests` job of `.github/workflows/tests.yml` and uploads the
report and traces when a test fails.

## Writing a test

- Personas (`admin`, `staffOfficer`, `moderator`, `eventCoordinator`, `member`, `handler`, `visitor`, `trooper`, `account`) sign in once in `auth.setup.js`; pick one with
  `test.use({ storageState: personas.member.storageState })`.
- Seed what a spec needs in `E2eSeeder` under an `E2E ...` name and list it in
  `tests/e2e/support/personas.js`. Specs run one at a time on one database, so a spec that
  changes data uses records no other spec relies on.
- To sign in as someone other than a persona, use `signInAndLeaveLogin()` so the next
  `page.goto()` does not race the login redirect.
- What a trooper would get by email (a signed verification link) comes from `support/artisan.js`,
  which runs `php artisan tinker` against the E2E database.
- Find things the way a trooper does: labels, roles and visible text, not CSS classes.
- Every new page gets a line in `pages.spec.js`; every new flow gets a spec.

## Coverage plan

"Full coverage" here means every user-facing page opens for each role that can reach it, every
role is refused what it can't reach, and every key flow is driven end to end. Status: ✅ done,
🟡 page opens only (flow not driven yet), ⬜ not started.

### Foundation (this PR)

| Area | Status |
|------|--------|
| Playwright setup, seeded database, CI job | ✅ |
| Sign in, wrong password, sign out, guest redirect | ✅ |
| Page smoke test for every parameter-free page, per role | ✅ |
| Member refused Command Staff; moderator refused admin-only pages | ✅ |
| Approve a pending recruit (moderator) | ✅ |
| Merge Costumes, Merge Troopers (admin) | ✅ |

### Event types (`event-types.spec.js`)

| Area | Status |
|------|--------|
| Event Types page opens for system owners, refused to moderators | ✅ |
| Create a non-troop-credit type; drag to reorder; hide from the event form; delete an unused type (not one in use) | ✅ |
| Warning on the edit page that changing "counts" also changes past events | ✅ |
| Event form preselects the first type and shows the chosen type's description and non-troop-credit note | ✅ |
| Members see the non-troop-credit notice on such an event, and not on a regular one | ✅ |

### Sign up and onboarding (`onboarding.spec.js`)

| Area | Status |
|------|--------|
| Sign-up page leads to the email form; the form is refused without starting sign-up | ✅ |
| Register as a member (club, ID, garrison, squad); a club is required | ✅ |
| New recruit confirms their email, lands on the pending screen, shows in the moderator's queue | ✅ |
| Verification notice, resend, signed link, tampered link | ✅ |
| Account setup for a trooper who has not finished it (required field, then gets in) | ✅ |
| Denied application: club fields appear when ticked, resubmit goes to pending | ✅ |
| Expired visitor is sent to renew; the request goes back for review | ✅ |
| Inactive account page | ✅ |

### Events, trooper side (`events.spec.js`)

| Area | Status |
|------|--------|
| List, calendar and map; completed troops listed apart | ✅ |
| Sign up, pick a costume and backup, add a guest and a friend, cancel | ✅ |
| Mission brief must be acknowledged before sign-up opens | ✅ |
| Watch and unwatch an event; calendar (.ics) download; roster download refused | ✅ |
| Upload a photo to the mission review | ✅ |
| Report attendance ("Made It") after a troop | ✅ |

The `trooper` persona (`E2E Trooper`) owns these sign-ups, so the notifications they send stay
out of the `member` persona's bell.

### Events, Command Staff side (`event-admin.spec.js`)

| Area | Status |
|------|--------|
| Create an event and view it; rename; copy; cancel (shows under cancelled, off the list) | ✅ |
| Edit and Copy shown to Command Staff on the event page, not to members | ✅ |
| Add a shift; add and remove a station; record charity | ✅ |
| Roster: add a trooper with the picker, remove them, change a status; roster CSV download | ✅ |
| Administrative upload, send to the mission review, delete | ✅ |
| Broadcast a message (a going trooper gets it); share a read-only roster link a guest can open | ✅ |

### Account (`account.spec.js`)

| Area | Status |
|------|--------|
| Profile: display name, phone and theme saved; display name required | ✅ |
| Notification frequency and per-club event notifications saved | ✅ |
| Costumes: find one, add it to the armory for a club, remove another | ✅ |
| Ask to join another club (shows as pending) | ✅ |
| Notices page; a notice on the event list until dismissed | ✅ |
| Request deletion (must type DELETE; signs out), sign back in and cancel | ✅ |

The `account` persona (`E2E Account Trooper`) owns these changes.

### Approval queues (`approvals-queues.spec.js`)

| Area | Status |
|------|--------|
| Deny an application with a reason; the recruit signs in and sees it | ✅ |
| Approve a request to join a squad (shows on the trooper's memberships); deny another | ✅ |
| A moderator asks for two troopers to become event coordinators; an administrator approves one and denies the other | ✅ |

### Trooper admin (`trooper-admin.spec.js`)

| Area | Status |
|------|--------|
| Search by email; filter by role | ✅ |
| Edit a profile (legal name, phone, status); the status change shows in the change log; legal name and a unique email are required | ✅ |
| A moderator can't open the profile of a trooper outside their clubs, and has no Authority tab | ✅ |
| Memberships: change the member ID, status and unit, then remove the membership | ✅ |
| Costumes and most recent troop tabs | ✅ |
| Recruit a trooper into a unit with a member ID | ✅ |
| Authority: make a trooper a moderator of a garrison (its squad follows) | ✅ |
| Guardian: a cadet under 18 needs the email of an adult already in the tracker | ✅ |
| Mark a trooper created in error (badge in the list) and another R.I.P. (memorial notice), through Status | ✅ |
| Flag do not trust with a reason, then clear it | ✅ |

### Costumes and costume credits (`costumes.spec.js`)

| Area | Status |
|------|--------|
| Create a costume (name required) and give it to a club | ✅ |
| Rename a costume; the name must be unused | ✅ |
| Delete a costume, with the warning that troopers will lose it | ✅ |
| Costume Approvals: approve one costume a trooper added, deny another | ✅ |
| Revoke a trooper's costume, then undo | ✅ |
| A do-not-trust trooper's "Made It" waits in Troop Verifications; a moderator approves one troop and removes another | ✅ |

### Awards, notices, organizations, FAQ (`content-admin.spec.js`)

| Area | Status |
|------|--------|
| A moderator creates an award (name required), renames it, gives it to a trooper, takes it back | ✅ |
| A moderator posts a notice to a garrison, copies it for next month, edits the copy (Active and Future lists) | ✅ |
| An administrator adds a unit under a garrison (name required), renames it, uploads a logo | ✅ |
| An administrator writes a FAQ section and question, a member reads it on the FAQ page, then both are deleted | ✅ |

### Service records and reports (`service-records.spec.js`)

| Area | Status |
|------|--------|
| A trooper's record: troops per club, History, Awards and Costumes tabs; Edit link only for Command Staff | ✅ |
| Leaderboard; a top costume's rankings | ✅ |
| Awards and achievements from the last 30 days; Command Staff page | ✅ |
| Missing Credit: assign a club, the troop then counts on the record | ✅ |
| Reports dashboard (day ranges), Event Summary, Event Types Counts, Troopers Without Activity, Status Change Log | ✅ |
| Trooper Event Summary, Donation Event Summary, Costume Used Most, and the leaderboard's club filter | ❌ These use MySQL-only SQL (`SUBSTRING_INDEX`, `JSON_OVERLAPS`) and fail on the SQLite test database |

### Plugins and system (`plugins-system.spec.js`)

| Area | Status |
|------|--------|
| A system owner saves a plugin's settings, enables it, then disables it | ✅ |
| System Check reports on PHP, the database and storage | ✅ |
| Plugins and System Check refused to moderators | ✅ |
| A shared event link: preview card tags and image for anyone, details only after signing in | ✅ |
| Search from the header for a trooper; search for an event | ✅ |

### Roles (`pages.spec.js`)

| Area | Status |
|------|--------|
| Handler and visitor open every member page and are refused Command Staff | ✅ |
| Event coordinator opens member pages and the Command Staff event pages, and is refused the rest | ✅ |
| Staff Officer opens moderator and staff pages, and is refused the developer tools (Plugins, System Check, Merge) | ✅ |

### Next PRs

| Area | Flows | Roles |
|------|-------|-------|
| MySQL-only reports | Run the browser job on MySQL in CI; Trooper Event Summary, Donation Event Summary, Costume Used Most, leaderboard club filter | moderator, member |

Each PR adds its pages and flows to the table above as it lands.
