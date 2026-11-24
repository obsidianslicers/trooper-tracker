# CONTRIBUTING.md

## Welcome, Trooper!

Thank you for your interest in contributing to the **501st Troop Tracker**! Your operational support is highly valued. Adherence to these guidelines ensures a swift and efficient contribution process.

## Code of Conduct

All contributors are expected to uphold the 501st Legion's standards of professionalism and respect. Please review and adhere to our [Code of Conduct](CODE_OF_CONDUCT.md).

---

## Contribution Methods

### 1. Issue Reporting (Bugs & Enhancements)

Issues are how you report a problem or propose a change. A small fix can go straight to a pull request; discuss larger changes in an issue first.

* **Bugs:** Open an issue labeled **`bug`**. Include steps to reproduce, screenshots, and relevant environment details.
* **Enhancements/Features:** Open an issue labeled **`enhancement`** or **`feature`**. Explain the feature's operational benefit and discuss major changes first.
* **Refactor:** Open an issue labeled **`refactor`**. Explain the change in core functionality.


### 2. Submitting Code Changes (Pull Requests)

For all code submissions, follow this structured process precisely:

1.  **Fork and Clone:** Fork the repository and clone your copy locally.

2.  **Create a Branch:** Branch from **`main`** and give the branch a short, descriptive name, such as `fix/event-date-on-mobile` or `refactor/thin-controllers`.

3.  **Make Changes:** Write your code following the conventions in [CLAUDE.md](CLAUDE.md) (architecture, naming, styling, testing) and [docs/CODING_CONVENTIONS.md](docs/CODING_CONVENTIONS.md). CLAUDE.md is written for AI coding assistants and people alike, and is the reference the team keeps current.
    * **Text people read must be translatable.** Use `__('<feature>.English text')` in PHP/Blade and `t('English text')` in Svelte, add the line to `tracker-app/lang/en/<feature>.php`, and run `php artisan tracker:report-translations --strict`. See [docs/LOCALIZATION.md](docs/LOCALIZATION.md).
    * **Adding a language?** Copy `lang/en` to `lang/<locale>` and translate it; [docs/LOCALIZATION.md](docs/LOCALIZATION.md#adding-a-language) has the steps.

4.  **Test:** Add or update tests for what you changed, then run the gates CI runs, from `tracker-app/`:
    * `php artisan test --parallel` (PHPUnit) and `npm test` (Vitest)
    * `npm run build` then `npm run test:e2e` (Playwright) when you change a page or a user flow
    * `composer pint:format` (formatting) and `php artisan tracker:report-translations --strict` (translations)

5.  **Commit Your Changes:** Use the format in [CLAUDE.md](CLAUDE.md#commit-messages):

    ```
    <type>: <short all lower case message>

    [optional body explaining WHY the change was made]
    ```

    | Type | When |
    | :--- | :--- |
    | `feature` | Something new people can do or see |
    | `fix` | Something that behaved wrongly now behaves correctly |
    | `style` | UI layout or look only, with no logic changed |
    | `refactor` | Restructuring or renaming with no change in behaviour |
    | `perf` | Faster or lighter, same behaviour |
    | `test` | Tests only |
    | `docs` | Documentation only |
    | `chore` | Everything else: dependencies, CI, build and tooling config, cleanup |

    One spelling per type (`feature`, not `feat`), no scope in parentheses. A change that fits more than one type takes the one that matters most to users: `feature` > `fix` > `perf` > `style` > `refactor` > `test` / `docs` > `chore`.

    **Example Commit:**
    ```
    fix: troop event dates show in the trooper's timezone on phones

    The date was formatted on the server in UTC; it is now localized the same
    way the desktop view does it.
    ```

6.  **Create a Pull Request (PR):**
    * Open a Pull Request from your branch to **`main`**.
    * **Title it like a commit** (`fix: ...`, `feature: ...`). PRs are squash-merged, so the PR title becomes the single commit on `main`.
    * If the PR resolves an issue, say so in the description (**`Closes #1234`**) so the issue closes on merge.

---

## Code Review & Merge

The project maintainer or designated reviewers will assess all Pull Requests. You may be required to make revisions; this is standard procedure to ensure stability. Your patience and cooperation ensure the tracker remains a high-quality resource.

Thank you for your service and adherence to protocol!