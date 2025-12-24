# GitHub Copilot Instructions for Laravel Project

This document provides instructions and context for GitHub Copilot to enhance its performance and align with project standards within this Laravel application.

## Project Overview

This is a Laravel application built with PHP. The application follows a clean, domain-driven architecture with a focus on maintainability, clarity, and expressive code. The primary authenticated entity in this system is a **Trooper**, not a generic User.

## Project Purpose

This application exists to support members of the **501st Legion** and other **Star Wars costuming clubs** by providing tools to:

- Track upcoming and past **troops** (events)
- Manage **trooper signups**, approvals, and attendance
- Coordinate event details between clubs (organizations), garrisons (regions), squads (units), and event organizers
- Provide a clear, friendly interface for troopers to see where they’re needed next

All domain language, models, factories, and tests should reflect this Star Wars costuming context.

## Current Versions

- **Laravel:** 12.x  
- **PHP:** 8.x  
- **Bootstrap:** 5.x  
- **HTMX:** 2.x  

## Coding Standards and Conventions

- **PSR-12:** Follow PSR-12 coding standards for PHP.
- **Laravel Conventions:** Use standard Laravel conventions for naming, structure, and implementation.
- **Readability:** Prioritize clear, expressive, self-documenting code.
- **DRY Principle:** Avoid duplication; extract reusable logic.
- **Meaningful Names:** Use descriptive, domain-driven names for classes, methods, and variables.
- **PHPDoc:** Use PHPDoc for classes, methods, and complex logic.

Additional conventions are documented here:  
**[Coding Conventions](../CODING_CONVENTIONS.md)**

## Preferred Libraries and Tools

- **Database:** MySQL  
- **Frontend:** Blade templates, Bootstrap CSS, HTMX
- **Testing:** PHPUnit for unit and feature tests    

## Project Structure and Key Areas

- **`app/`** — Models, Controllers, Services, Actions, Policies  
- **`database/`** — Migrations, Seeders, Factories  
- **`resources/views/`** — Blade templates  
- **`routes/`** — Web and API routes  
- **`tests/`** — Unit and Feature tests  

---

# Domain Vocabulary

Copilot must use the correct domain language:

- The primary authenticated model is **Trooper**, not User.
- Avoid generating references to `User`.
- Use `Trooper::class`, `Trooper::factory()`, and `$this->actingAs($trooper)` in examples.
- Factories should use domain states such as:
  - `Trooper::factory()->pending()`
  - `Trooper::factory()->retired()`
  - `Trooper::factory()->approved()`


---

# Specific Instructions for Copilot

### General Code Generation

- Prefer Eloquent ORM over raw SQL.
- Include appropriate relationships (`hasMany`, `belongsTo`, etc.) based on context.
- Suggest Laravel helpers and facades where appropriate.
- Generate Blade templates using Bootstrap 5.x conventions.
- Avoid deprecated Laravel features or outdated PHP syntax.
- When refactoring, aim for smaller, focused classes and methods.

---

# Feature Test Philosophy (Non‑Brittle, Behavior‑Driven)

Copilot must generate **refactor‑friendly, behavior‑driven** tests.

### Test Behavior, Not Implementation

- Focus on **what the Trooper experiences**, not how the code is structured.
- Do not reference controllers, method names, or view files.

### Avoid Brittle Assertions

Do **not** assert:

- Exact HTML markup  
- CSS classes  
- DOM structure  
- Specific database columns or table names  

Prefer:

- `assertSeeText()`  
- `assertRedirect()`  
- `assertSessionHas()`  
- High‑level JSON structure assertions  

### Use Domain‑Driven Factories and States

Examples:

```php
$trooper = Trooper::factory()->veteran()->create();
$troop = Troop::factory()->upcoming()->create();
```