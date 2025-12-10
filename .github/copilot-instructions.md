# GitHub Copilot Instructions for Laravel Project

This document provides instructions and context for GitHub Copilot to enhance its performance and align with project standards within this Laravel application.

## Project Overview

This is a Laravel application built with PHP. We are using Laravel version X.Y and PHP version Z.W. The application follows a standard MVC architecture with a focus on clean code and maintainability.

## Current Versions

-   **Laravel:** 12.x
-   **PHP:** 8.x
-   **Bootstrap:** 5.x
-   **HTMX:** 2.x

## Coding Standards and Conventions

-   **PSR-12:** Adhere to the PSR-12 coding standard for PHP.
-   **Laravel Conventions:** Follow standard Laravel conventions for naming, directory structure, and feature implementation (e.g., using Eloquent, Blade templates, etc.).
-   **Readability:** Prioritize clear, readable, and self-documenting code.
-   **DRY Principle:** Avoid code duplication.
-   **Meaningful Names:** Use descriptive names for variables, functions, classes, and files.
-   **PHPDoc:** Utilize PHPDoc blocks for classes, methods, and complex logic to improve documentation.

Additionally, coding conventions are located here: [Coding Conventions](../CODING_CONVENTIONS.md)

## Preferred Libraries and Tools

-   **Database:** MySQL.
-   **Frontend:** Blade templates, Tailwind CSS, Alpine.js (or Livewire if applicable).
-   **Testing:** PHPUnit for unit and feature tests.
-   **Linting/Formatting:** PHP CS Fixer, Laravel Pint.

## Project Structure and Key Areas

-   **`app/`:** Contains core application logic (Models, Controllers, Services, etc.).
-   **`database/`:** Migrations, Seeders, Factories.
-   **`resources/views/`:** Blade templates.
-   **`routes/`:** Web and API routes.
-   **`tests/`:** Unit and Feature tests.

## Specific Instructions for Copilot

-   When generating Eloquent models, include common relationships (e.g., `hasMany`, `belongsTo`) based on context.
-   Suggest appropriate Laravel facades and helper functions.
-   Generate Blade template snippets following Bootstrap 5.x CSS conventions.
-   When creating tests, prioritize feature tests for controllers and unit tests for specific classes.
-   Avoid suggesting deprecated Laravel features or outdated PHP syntax.
-   When refactoring, aim for smaller, more focused methods and classes.
-   For database interactions, prefer Eloquent unless raw SQL is explicitly necessary.