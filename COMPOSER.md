# Composer Dependencies

This document outlines the Composer dependencies used in the Troop Tracker project. It is intended to help developers understand the purpose of each package.

## Production Dependencies (`require`)

These packages are required for the application to run in a production environment.

| Package | Purpose |
|---|---|
| `php` | The underlying programming language version for the project (^8.2). |
| `google/apiclient` | The official Google API Client Library for PHP. Used for integrating with Google services like Google Sheets for organization roster synchronization. |
| `intervention/image` | A PHP image handling and manipulation library. Used for processing and resizing uploaded event photos and trooper avatars. |
| `laravel/framework` | The core of the application. Laravel is the PHP framework upon which the application is built. |
| `laravel/socialite` | OAuth authentication library for Laravel. Used for social login integrations (Google OAuth and XenForo OAuth for forum-based authentication). |
| `laravel/tinker` | An interactive REPL (Read-Eval-Print Loop) for Laravel. Allows developers to interact with the application and its objects via the command line. |
| `spatie/calendar-links` | A library to generate "Add to Calendar" links for various calendar services (Google, iCal, Outlook). Enhances user experience for event management. |

---

## Development Dependencies (`require-dev`)

These packages are only used for local development and testing. They are not installed in the production environment.

| Package | Purpose |
|---|---|
| `barryvdh/laravel-debugbar` | A debugging toolbar for Laravel that displays queries, view data, route information, and performance metrics during development. |
| `fakerphp/faker` | A library for generating fake data. Essential for populating the database with test data (database seeding) for development and automated testing. |
| `laravel/boost` | A Laravel package that provides performance optimizations and pre-compilation for production deployments. |
| `laravel/pail` | A command-line tool for tailing Laravel application logs in real-time with helpful filtering and formatting capabilities. |
| `laravel/pint` | An opinionated PHP code style fixer built on top of PHP-CS-Fixer. Maintains consistent coding style across the project following Laravel conventions. |
| `laravel/sail` | A command-line interface for managing Laravel's default Docker development environment, simplifying local development setup. |
| `mockery/mockery` | A mock object framework used in unit tests. Allows creation of test doubles (mocks) to isolate code during testing. |
| `nunomaduro/collision` | Provides beautiful and detailed error reporting for command-line PHP applications, improving the debugging experience during development. |
| `phpunit/phpunit` | The standard testing framework for PHP. Used to write and run unit, feature, and integration tests for the application. |
| `reliese/laravel` | A code generation tool that creates Eloquent Models and related classes directly from the database schema, speeding up development. |

---

## Composer Scripts

The `composer.json` file also contains several scripts to automate common tasks:

*   **`setup`**: A convenient script to set up the project for a new developer. It installs Composer and NPM dependencies, creates the `.env` file, generates an application key, and runs database migrations.
*   **`dev`**: Starts all the necessary services for local development concurrently: the PHP server, the queue worker, the log trailer (`pail`), and the Vite asset bundler.
*   **`test`**: Runs the application's automated test suite using PHPUnit.

For more details on other scripts, refer to the `scripts` section in the `composer.json` file.

## A Note on `reliese/laravel`

You specifically highlighted the `reliese/laravel` package.

*   **Package**: `reliese/laravel`
*   **Purpose**: This is a development tool that helps accelerate the creation of boilerplate code. It can inspect your database schema and automatically generate corresponding Eloquent Models, including properties, relationships (`HasOne`, `BelongsTo`, etc.), and PHPDoc blocks. This saves significant time during development, especially when dealing with a large number of database tables. It is a `dev` dependency because it's not needed for the application to run in production.
