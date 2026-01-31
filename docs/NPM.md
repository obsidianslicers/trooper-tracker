# NPM Dependencies

This document provides an overview of all NPM packages used in the Troop Tracker project. It is designed to help new contributors understand the frontend technology stack.

## Production Dependencies (`dependencies`)

These packages are bundled into the frontend application and shipped to production.

| Package | Purpose |
|---|---|
| `@alpinejs/collapse` | Alpine.js plugin that provides smooth collapse/expand transitions for elements, used for accordion-style UI components. |
| `@alpinejs/intersect` | Alpine.js plugin that triggers functionality when elements enter or leave the viewport, useful for lazy-loading and scroll-based animations. |
| `@alpinejs/mask` | Alpine.js plugin that provides input masking functionality for formatted text inputs like phone numbers, dates, and credit cards. |
| `@alpinejs/persist` | Alpine.js plugin that automatically persists Alpine component state to localStorage, maintaining UI state across page reloads. |
| `alpinejs` | A lightweight JavaScript framework for adding interactivity to HTML. Provides reactive data binding and component behavior without a build step. |
| `bootstrap` | A comprehensive CSS framework providing pre-built UI components, responsive grid system, and utilities for rapid frontend development. |
| `easymde` | A simple, embeddable, and beautiful Markdown editor with features like preview, full-screen mode, and side-by-side editing. |
| `flatpickr` | A lightweight and powerful datetime picker with no dependencies, offering extensive customization options and a clean user interface. |
| `htmx.org` | A library that allows access to modern browser features (AJAX, CSS Transitions, WebSockets) directly in HTML using attributes, enabling dynamic page updates without writing JavaScript. |
| `jquery` | A fast, small, and feature-rich JavaScript library that simplifies DOM manipulation, event handling, and AJAX interactions. |
| `typeahead.js` | A flexible JavaScript library that provides autocomplete/typeahead functionality for input fields with customizable suggestion engines. |

## Development Dependencies (`devDependencies`)

These packages are only used during development and the build process. They are not shipped to production.

| Package | Purpose |
|---|---|
| `@tailwindcss/vite` | Official Vite plugin for Tailwind CSS v4, enabling seamless integration of Tailwind's utility-first CSS framework in the build process. |
| `axios` | A promise-based HTTP client for making API requests. Provides a simple interface for performing AJAX operations with automatic JSON transformation. |
| `concurrently` | A command-line utility that runs multiple NPM scripts simultaneously, useful for running development servers and build watchers in parallel. |
| `laravel-vite-plugin` | Official Laravel plugin for Vite that handles asset compilation, hot module replacement (HMR), and integrates seamlessly with Laravel's Blade templates. |
| `sass` | A CSS preprocessor that extends CSS with features like variables, nested rules, mixins, and functions, making stylesheets more maintainable. |
| `tailwindcss` | A utility-first CSS framework that provides low-level utility classes for building custom designs without writing CSS from scratch. |
| `vite` | A modern frontend build tool that provides fast development server with hot module replacement and optimized production builds using native ES modules. |

## NPM Scripts

The following commands are defined in `package.json` and can be run with `npm run <script>`:

| Script | Command | Purpose |
|---|---|---|
| `dev` | `vite` | Starts the Vite development server with hot module replacement (HMR). Use this during active development for instant feedback on frontend changes. |
| `build` | `vite build` | Creates an optimized production build of all frontend assets. Run this before deploying to production to generate minified, bundled assets. |

## Frontend Architecture

### JavaScript Framework Stack

The application uses a **multi-framework approach** optimized for progressive enhancement:

- **HTMX (htmx.org):** Primary driver for dynamic page updates and AJAX interactions. HTMX extends HTML with attributes that trigger server requests and swap content, minimizing the need for custom JavaScript.
- **Alpine.js:** Provides reactive components and interactivity for client-side UI behavior. Alpine complements HTMX by handling local state management and UI interactions that don't require server communication.
- **jQuery:** Legacy support for specific plugins (typeahead.js) and DOM manipulation in older code sections. Gradually being replaced with HTMX and Alpine patterns.

### CSS Framework Stack

- **Bootstrap 5.2.3:** Primary CSS framework providing the foundational grid system, components, and utilities. Used for consistent UI patterns across the application.
- **Tailwind CSS 4.0:** Used for utility-first styling and custom component development. Integrated via Vite plugin for optimal development experience.
- **Sass:** CSS preprocessor for custom styles and Bootstrap customization, allowing for variables, mixins, and modular stylesheets.

### Build Tool

- **Vite 7:** Modern build tool replacing Laravel Mix. Provides:
  - Lightning-fast development server with HMR
  - Optimized production builds with code splitting
  - Native ES module support
  - Seamless Laravel integration via `laravel-vite-plugin`

## Development Workflow

### Starting Development

```bash
# Start Vite development server with HMR
npm run dev
```

This command starts the Vite development server, which watches for file changes and automatically reloads the browser. The `laravel-vite-plugin` ensures proper integration with Laravel's Blade templates.

### Building for Production

```bash
# Create optimized production build
npm run build
```

This generates minified, bundled assets in the `public/build` directory, ready for deployment. The build process automatically handles:
- JavaScript bundling and tree-shaking
- CSS minification and optimization
- Asset versioning for cache busting
- Source map generation

## Package Categories

### UI Components & Interactivity
- Bootstrap (components, grid, utilities)
- Alpine.js + plugins (reactive components)
- HTMX (dynamic content loading)
- Flatpickr (date/time picking)
- EasyMDE (Markdown editing)
- Typeahead.js (autocomplete)

### Build & Development Tools
- Vite (build tool and dev server)
- Laravel Vite Plugin (Laravel integration)
- Concurrently (parallel script execution)
- Sass (CSS preprocessing)

### CSS Frameworks & Tooling
- Bootstrap 5.2.3 (component framework)
- Tailwind CSS 4.0 (utility-first framework)
- @tailwindcss/vite (Tailwind integration)

### Utilities & Libraries
- Axios (HTTP client)
- jQuery (DOM manipulation, legacy support)

## Migration Notes

### From Laravel Mix to Vite

This project has migrated from Laravel Mix to Vite for improved development experience and build performance. Key differences:

- **Hot Module Replacement:** Vite provides near-instant HMR compared to Mix's full-page reloads
- **Build Speed:** Vite leverages native ES modules and esbuild for dramatically faster builds
- **Configuration:** Vite configuration is in `vite.config.js` (previously `webpack.mix.js`)

### Progressive Enhancement Strategy

The application follows a progressive enhancement approach:

1. **HTML First:** All functionality works with server-rendered HTML
2. **HTMX Layer:** Adds dynamic updates without full page reloads
3. **Alpine.js Layer:** Adds client-side reactivity for improved UX
4. **jQuery (Legacy):** Gradually being phased out in favor of HTMX/Alpine patterns

New features should prioritize HTMX and Alpine.js over jQuery when possible.

## Additional Resources

For more detailed information, refer to:

- [Vite Documentation](https://vitejs.dev/)
- [Laravel Vite Plugin Documentation](https://laravel.com/docs/vite)
- [HTMX Documentation](https://htmx.org/)
- [Alpine.js Documentation](https://alpinejs.dev/)
- [Bootstrap Documentation](https://getbootstrap.com/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
