# NPM Dependencies

Overview of frontend packages and build tools.

## Production Dependencies

Bundled into the frontend application:

| Package | Purpose |
|---|---|
| `@inertiajs/core`, `@inertiajs/svelte` | Inertia: server-side routing and controllers that render Svelte pages without a separate API. |
| `bootstrap` | CSS framework and its JavaScript components (modals, collapses, tooltips, toasts). |
| `easymde` | Markdown editor, imported by the Svelte editor component. |
| `sortablejs` | Drag-and-drop reordering (FAQ items, event types, shifts). |
| `svelte` | The component framework every interactive page is written in. |
| `vanilla-calendar-pro` | The date picker behind `resources/js/custom/date-picker.js`. |
| `ziggy-js` | Laravel's named routes in JavaScript (`route('events.show', ...)`). |

## Development Dependencies

Used during development, testing and the build only:

| Package | Purpose |
|---|---|
| `@playwright/test` | Browser tests in `tests/e2e`. |
| `@sveltejs/vite-plugin-svelte` | Compiles Svelte components in Vite. |
| `@tailwindcss/vite`, `tailwindcss` | Tailwind CSS integration for the build. |
| `@testing-library/svelte`, `jsdom`, `vitest` | Front-end unit tests in `tests/js`. |
| `@tsconfig/svelte`, `svelte-check` | TypeScript settings and the type check (`npm run check`). |
| `@types/bootstrap`, `@types/sortablejs` | Types for the TypeScript code. |
| `axios` | HTTP client, set up as `window.axios` in `resources/js/bootstrap.js`. |
| `concurrently` | Runs several scripts in parallel during development. |
| `laravel-vite-plugin` | Laravel's Vite integration: entry points, HMR and the build manifest. |
| `sass` | Compiles `resources/css/app.scss`. |
| `vite` | Dev server and production build. |

## NPM Scripts

The following commands are defined in `package.json` and can be run with `npm run <script>`:

| Script | Command | Purpose |
|---|---|---|
| `dev` | `vite` | Starts the Vite development server with hot module replacement (HMR). |
| `build` | `vite build` | Creates an optimized production build in `public/build`. |
| `check` | `svelte-check` | Type-checks the Svelte and TypeScript code. |
| `test` | `vitest run` | Runs the front-end unit tests. |
| `test:e2e` | `playwright test` | Runs the browser tests (needs `npm run build`). |
| `test:e2e:parallel` | `node tests/e2e/support/parallel.js` | The browser tests, split over several app servers. |

## Frontend Architecture

- **Svelte 5 + Inertia 2 + TypeScript:** every interactive page. See the Svelte section of `CLAUDE.md`.
- **Blade:** static, server-rendered pages (home, FAQ, email landers, shares) and emails.
- **Bootstrap 5.2.3:** the look of both, with the shared classes in `resources/css/app.scss`.
- **Plain JavaScript (`resources/js`):** the small helpers Blade pages share (toasts, tooltips,
  forms), loaded by `resources/js/app.js`, plus modules the Svelte code imports (date picker,
  request parsers).

## Additional Resources

- [Vite Documentation](https://vitejs.dev/)
- [Laravel Vite Plugin Documentation](https://laravel.com/docs/vite)
- [Svelte Documentation](https://svelte.dev/docs)
- [Inertia Documentation](https://inertiajs.com/)
- [Bootstrap Documentation](https://getbootstrap.com/)
