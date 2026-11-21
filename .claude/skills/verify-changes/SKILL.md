---
name: verify-changes
description: Check a trooper-tracker change before calling it done, committing it or opening a PR. Picks the tests that cover the change, then runs the gates CI runs (PHPUnit in parallel, Pint, Vitest, translations, Playwright) the fast way. Use after editing code, or when asked to run, verify or validate tests.
---

# Verify a change

Run everything from `tracker-app/`. Work outward: the tests closest to the change first, so a
failure shows up in seconds, then the gates CI runs before you call the work done.

## 1. The tests that cover the change

| Changed | Run |
|---|---|
| A class in `app/` | Its test, which mirrors the path: `php artisan test tests/Unit/Messages/Events/...Test.php`, or `--filter=ClassNameTest` |
| A controller, job, command or route | `tests/Feature/...` for it (see "Test type by component" in `CLAUDE.md`) |
| A plugin (`plugins/<slug>/src`) | `php artisan test plugins/<slug>/tests` |
| `resources/js` or Svelte | `npx vitest run tests/js/<same path>` |
| A page or user flow | Its spec: `npx playwright test events` (needs built assets, see step 3) |
| A migration or seeder | `php artisan test tests/Feature/Database` |

Add or update tests for what changed (`CLAUDE.md`, "Testing"). A test you wrote should fail
without the change. Check that once if it's cheap.

## 2. The gates CI runs

| Gate | Command | Time |
|---|---|---|
| PHPUnit, whole suite | `php artisan test --parallel` | ~10 s on 8+ cores (~1 min with plain `php artisan test`) |
| Formatting | `composer pint:test` (fix with `composer pint:format`) | ~20 s |
| Front-end unit tests | `npm test` | ~5 s |
| Translations | `php artisan tracker:report-translations` | seconds |
| Svelte types (not in CI yet, but cheap) | `npm run check` | ~5 s |

`--parallel` prints only failures and the totals. Keep the output short with
`php artisan test --parallel 2>&1 | tail -40`. If a test fails only under `--parallel`, it is
sharing state with another test (a fixed file path, say). Run it alone to confirm, and fix the
test rather than dropping `--parallel`.

## 3. Browser tests, for UI or flow changes

```bash
npm run build                  # the tests use the built assets
npm run test:e2e:parallel      # whole suite in shards, ~2.5 min instead of ~5
npx playwright test approvals  # or one spec
```

`docs/E2E_TESTING.md` has the details: personas, the seeded data, writing a spec. If a test fails,
run its spec on its own once before treating it as real; the traces and screenshots are in
`test-results/`. Run `npx playwright install chromium` once per machine.

## Things that skip or differ locally

- PHPUnit runs on in-memory SQLite (`phpunit.xml`). Its `<env>` values are defaults, not forced,
  so a shell or container that sets `DB_DATABASE` or `DB_CONNECTION` wins. Run as
  `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test` there.
- About 50 PHPUnit tests and 4 browser tests need MySQL and skip on SQLite. CI runs the browser
  tests on MySQL (`E2E_DB_CONNECTION=mysql`, see `docs/E2E_TESTING.md`). If a change touches a
  report or raw SQL, run those on MySQL too.

## Reporting

Say which gates you ran and what each showed, with the numbers (for example, "3,809 tests, 0
failures"). Name any gate you skipped and why. Never describe a gate you didn't run as passing.
