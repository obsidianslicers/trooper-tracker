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

- Personas (`admin`, `moderator`, `member`) sign in once in `auth.setup.js`; pick one with
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

### Next PRs

| # | Area | Flows | Roles |
|---|------|-------|-------|
| 2 | Events (trooper side) | List, calendar, map, details, sign up / change status / cancel, guests, mission brief ack, calendar and roster downloads, uploads | member, handler |
| 3 | Events (Command Staff) | Create, copy, update, shifts and stations, roster management, charity, uploads, mission review, cancel | moderator, admin |
| 4 | Account | Profile, notification settings, costumes add/remove, club requests, notices, push notifications, deletion request | member |
| 5 | Approval queues | Deny recruit, approve/deny club requests, membership lookup, request and approve/deny permissions | moderator, admin |
| 6 | Trooper admin | List and search, profile edit, authority, membership, guardian, events, costumes, change log, void/unvoid, RIP, recruit | moderator, admin |
| 7 | Costumes and costume credits | Costume CRUD, merge edge cases; costume gate and do-not-trust flag once PRs #492 and #493 merge | member, moderator, admin |
| 8 | Awards, notices, organizations, FAQ | Create/update, assign troopers to awards, organization tree and images, FAQ sections and items reorder | moderator, admin |
| 9 | Service records and reports | Trooper record, leaderboard, awards, achievements, photo gallery, costume pages; every report with filters, missing credits assign | member, moderator |
| 10 | Plugins and system | Plugins page enable/disable/settings, system check, public shares and OG images, search | admin, guest |
| 11 | Roles not yet seeded | Handler, visitor, and Staff Officer (once PR #491 merges) across the page smoke test | all |

Each PR adds its pages and flows to the table above as it lands.
