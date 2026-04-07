# CODING COPILOT PROMPTS

## PHPDoc Comments

This prompt performs a structured audit of PHPDoc blocks within a specified directory. It reviews every class and its public methods, correcting and updating class‑level and method‑level documentation so it accurately reflects the existing code. It removes stale or incorrect annotations, avoids modifying signatures or logic, and focuses solely on improving clarity, correctness, and IDE type inference. The prompt applies recursively through subfolders, respects any excluded paths, and updates only the PHPDoc content while leaving all functional code untouched.

```
Role & Objective
You are a **Lead PHP Architect**. Your goal is to analyze the PHP classes in `TARGET_PATH` and ensure all methods, properties, and class headers have accurate, clean, and informative PHPDoc blocks.

Configuration
- TARGET_PATH: `\app\?`
- Standard: PSR-5 and PSR-19 (PHPDoc tags)
- Typing: Strict PHP 8+ type hinting
- Model Metadata Source: `app/Models/Base/**` is the canonical source for generated schema PHPDoc.

Strict Constraints

Annotation Rules
- Redundancy: If a method has native PHP type hints that are fully descriptive (e.g., `public function save(User $user): bool`), do not add a redundant `@param` or `@return` unless it requires an explanation or a specific array shape.
- Array Shapes: For arrays, use generics-style notation or `object-shape` if possible. 
    - *Example:* `array<string, int>` or `User[]`.
- Inheritance: Use `{@inheritDoc}` when a method is strictly implementing an interface method without adding unique behavior.
- No Guessing: Do not document behavior, exceptions, or properties that cannot be verified from code, migrations, routes, or generated base models.

Content & Tone
- Clarity: Summaries should start with a third-person singular verb (e.g., "Calculates," "Retrieves," "Authenticates").
- Exceptions: Include `@throws` tags only for exceptions explicitly thrown in the method body or clearly part of the method contract via immediate calls.
- Relations: Use **snake_case** relation names matching relationship methods.

Eloquent Model Rules
- Base Models (`app/Models/Base/**`): Keep complete generated schema and relationship `@property` tags.
- Child Models (`app/Models/**` extending a base model): Do not duplicate base `@property` tags.
- Child Model Deltas: Add only child-specific PHPDoc, such as:
   - cast-driven type refinements (e.g., `status` as enum instead of string)
   - relationships declared only in the child model
   - child-specific computed/virtual properties

Execution Guardrails
- Logic Integrity: **DO NOT** change any executable PHP code (variables, logic, method signatures, or return types). You are only permitted to modify the comment blocks (`/** ... */`).
- Imports: If a PHPDoc refers to a class not currently imported, add the necessary `use` statement at the top of the file.

Workflow
1. **Analyze: Scan the file for missing or outdated PHPDocs.
2. **Scan Sources: If the class is a Controller or Model, check `routes/**`, migrations, and any generated base model to ensure documentation matches actual data flow and schema ownership.
3. **Draft: Generate the updated DocBlocks.
4. **Verify: Ensure the file still passes static analysis (no syntax errors introduced in the comment blocks).

PowerShell Verification
Provide this command to check for syntax errors after the update:
`php -l [FILE_PATH]`
```

## Provide Tests

A test‑coverage audit ensures every class under a given path has a corresponding, meaningful test suite. It standardizes expectations for contributors, prevents silent regressions, and keeps the codebase maintainable as it grows.

```
Role & Objective
You are a **Senior Laravel QA Engineer**. Your goal is to generate comprehensive, production-ready tests for all PHP classes within the specified `TARGET_PATH`.

Configuration
- TARGET_PATH: `\app\??`
- Framework: Laravel 12+ / PHP 8+
- Test Runner: PHPUnit (follow mirrored directory structure)
- Data Layer: Eloquent Factories (Builder Pattern favored)

Strict Constraints

File Mapping & Conventions
- Mirroring: Tests must be placed in a corresponding path within the `tests/` directory.
    - *Example:* `app/Services/Analytics/ReportGenerator.php` → `tests/Feature/Services/Analytics/ReportGeneratorTest.php`
- Naming: Use PascalCase for classes and descriptive snake_case for test methods starting with "test_".
- Relationships: Ensure all Eloquent relations are accessed via **snake_case**.

Data & State Management
- Factory Builders: Use Laravel Factories for all database interactions.
- State Logic: If a specific model state is required, do not manually override attributes in the test. Instead, enhance or create **Factory States** or **Builder methods** within the factory file.
- Database: Always include `use RefreshDatabase;` or `use DatabaseTransactions;` as appropriate.

Routing & Context
- Route Inspection: Before generating Feature/Integration tests, inspect `app\routes\**` to ensure correct URI, Middleware, and HTTP Verb usage.
- Service Container: Use `$this->mock()` or `$this->instance()` for external dependencies (ie google, xenforo, mail, or http), but prefer real execution for internal logic where possible.

Permissions & Execution
- Source Integrity: **DO NOT** modify any source code within the `app/` directory. 
- Write Access: You have full permission to create/modify files in `tests/` and `database/factories/`.
- Validation: Provide a **PowerShell** command to run the specific generated test (e.g., `php artisan test tests/Feature/Path/To/FileTest.php`).

Workflow
1. **Analyze: Identify the class dependencies and injected contracts.
2. **Setup: Create/Update the necessary Factories and States.
3. **Generate: Write the test file in the mirrored `tests/` path.
4. **Verify: Output the PowerShell snippet to check the results.
```

## Clean up Usings

It instructs Copilot Chat to scan all PHP files under app/**, clean up their use statements by removing unused or duplicate imports and collapsing redundant namespace entries, and to perform the entire refactor without leaving behind any temporary or analysis files—deleting them immediately if they are created—so that only the original project files remain after the cleanup.

```
Scan the entire workspace (limited to the app/** folder) and clean up all PHP use statements.

For every PHP file:
- remove unused use statements
- remove duplicate imports
- collapse multiple imports from the same namespace when appropriate

During this process:
- do not create any new files for analysis
- if you generate any temporary or analysis files, delete them immediately after use
- ensure the workspace contains only the original project files after the refactor
```

## Update the Database Diagram

This prompt generates a complete docs/DATABASE.md file, including table structures, column definitions, constraints, inferred relationships, and a Mermaid ER diagram visualizing table dependencies. The output provides an up‑to‑date, human‑readable reference for the database schema.

```
Role & Objective
You are a **Database Architect**. Your task is to perform a static analysis of all Laravel migration files and generate a definitive `/DATABASE.md` file that serves as the "Source of Truth" for the application's schema.

Configuration
- Source Material: `database/migrations/*.php`
- Output File: `/DATABASE.md`
- Diagram Engine: Mermaid.js (`erDiagram`)
- Naming Convention: Laravel Snake Case (Eloquent Standard)

Strict Constraints

Analysis Requirements
- Comprehensive Scan: Inspect every migration file to map:
    - Tables, Columns, and Data Types (including `unsignedBigInteger`, `uuid`, etc.).
    - Constraints: Primary Keys, Unique Indexes, and Spatial Indexes.
    - Laravel Helpers: `softDeletes()`, `timestamps()`, `rememberToken()`, and `morphs()`.
- Relationship Discovery: Identify Foreign Keys (`constrained()`), Pivot Tables (by name or `belongsToMany` logic), and Polymorphic relations.

Documentation Structure
The `docs/DATABASE.md` must contain:
- Entity Relationship Diagram: A Mermaid `erDiagram` at the top. Use correct cardinality (e.g., `||--o{` for one-to-many).
- High-Level Overview: A summary of the database's purpose and architectural style (e.g., "Standard Relational with Polymorphic Meta-tables").
- Table Dictionary: A section for each table including:
    - Purpose: Infer from table/column naming (e.g., `order_items` handles line items for purchases).
    - Schema Table: A Markdown table listing Column, Type, Nullable, and Key constraints.
    - Relationships: A bulleted list of "Belongs To," "Has Many," or "Morphs To" links.

Formatting & Logic
- No Hallucinations: Do not guess column names. Only document what is explicitly defined in the migrations.
- Markdown Excellence: Use GitHub-flavored Markdown with clear heading hierarchies and code blocks for the Mermaid diagram.
- Integrity: **DO NOT** modify any existing migration or application files. Only create or update `docs/DATABASE.md`.
- Bounded Contexts: Create different merdmaid diagrams using logical bounded contexts, for example: Troopers, Events, Organizations, etc.

Workflow
1. **Inventory: List all discovered tables from the migrations.
2. **Map: Trace foreign key paths to establish the Mermaid diagram logic.
3. **Draft: Construct the table-by-table dictionary.
4. **Finalize: Output the complete contents of `docs/DATABASE.md` for review.

Verification Command (PowerShell)
To verify the migration files exist before starting:
`Get-ChildItem -Path database/migrations -Filter *.php | Measure-Object`
```

## Ensure all files use strong type checks

It checks every PHP file under app/** except app/Models/Base/** to ensure the first non‑comment line is declare(strict_types=1);, inserting or moving it to the top if needed while leaving all other code, comments, namespaces, and formatting untouched. Copilot should apply fixes directly, report modified files, and ensure each file begins with <?php, then the strict types declaration, followed by the namespace and use statements.

```
Scan every PHP file under app/** with the exception of app/Models/Base/** and verify that the first non-comment line is:

declare(strict_types=1);

For each file:
- If the declaration is missing, insert it as the very first line.
- If it exists but is not the first line, move it to the top.
- Do NOT modify any other code, comments, namespaces, imports, or formatting.
- Preserve all spacing and file structure except what is required to correctly place the declaration.
- Apply fixes directly to the files.
- Report which files were modified.

the start of each file should look like the following template
<?php

declare(strict_types=1);

namespace ...

use statements ... etc
```

## Update DOCS


### Update docs/

This prompt instructs Copilot to scan the entire app/ directory, derive an accurate understanding of all Laravel components, and update the Markdown files in docs/ to match the current codebase. It preserves each document’s tone and structure, fixes outdated references, adds missing sections, and removes obsolete content. Copilot must pause and request clarification whenever any ambiguity or conflict arises before making changes, then apply updates only within docs/ and report which files were modified and why.

```
You are auditing this Laravel application.

1. Analyze the entire app/ directory:
   - Identify all models, relationships, enums, jobs, listeners, events, policies, services, traits, and console commands.
   - Detect any new classes, renamed classes, removed classes, or signature changes.
   - Infer architectural patterns, domain boundaries, and conventions used in the codebase.

2. Using that analysis, prepare updates for every Markdown file in the docs/ directory:
   - Ensure all documentation accurately reflects the current state of the codebase.
   - Fix outdated references, class names, method names, and architectural descriptions.
   - Add missing sections when new features or components exist in app/.
   - Remove or rewrite sections that no longer match the code.
   - Preserve the existing tone, structure, and voice of each document.
   - Maintain consistent formatting, headings, code fences, and terminology.

3. Before modifying any file:
   - If any ambiguity, conflict, missing context, or unclear mapping arises between the code and the documentation,
     STOP and ask me for clarification before making changes.
   - Provide a concise description of the issue and the options for resolving it.

4. After all clarifications are resolved:
   - Apply changes directly to the affected docs/ files.
   - Do not modify anything outside docs/.
   - Do not explain the changes unless asked.

5. When finished:
   - Report which docs were updated and why.
   - Do not summarize the codebase; only summarize documentation changes.

Begin by scanning app/ and identifying any issues that require clarification.
```

### README

```
You are auditing this Laravel application.

1. Analyze the entire app/ directory:
   - Identify all major subsystems, architectural patterns, domain boundaries, and conventions.
   - Detect new features, renamed components, removed components, or significant structural changes.
   - Understand the project’s current capabilities at a high level without rewriting business logic.

2. Using that analysis, prepare updates for the project’s README.md:
   - Ensure the README accurately reflects the current architecture, features, and conventions.
   - Fix outdated descriptions, terminology, or references.
   - Add missing high‑level sections when new subsystems or capabilities exist.
   - Remove or rewrite sections that no longer match the codebase.
   - Preserve the README’s existing tone, structure, and voice.
   - Maintain consistent formatting, headings, code fences, and terminology.

3. Before modifying the README:
   - If any ambiguity, conflict, missing context, or unclear mapping arises between the code and the README, STOP and ask me for clarification before making changes.
   - Provide a concise description of the issue and the options for resolving it.

4. After all clarifications are resolved:
   - Apply changes directly to README.md.
   - Do not modify anything outside README.md.
   - Do not explain the changes unless asked.

5. When finished:
   - Report what sections were updated and why.
   - Do not summarize the codebase; only summarize documentation changes.

Begin by scanning app/ and identifying any issues that require clarification.
```

### All DOCS

```
Audit all documentation files in the docs/ folder and the README with the following goals:

1. Make every document concise and focused. Remove filler, redundant explanations, and repeated architectural descriptions that already exist elsewhere.

2. Ensure each document has a clear, unique purpose. No file should duplicate content found in README, ARCHITECTURE, PROJECT_STRUCTURE, or other docs. Consolidate or rewrite sections to eliminate overlap.

3. Create a consistent hierarchy across all docs:
   - README = high-level overview + contributor quickstart + links into docs
   - docs/ARCHITECTURE = full system architecture (ADR, MagicBus, domains, data flow)
   - docs/PROJECT_STRUCTURE = directory and subsystem layout
   - docs/DATABASE = schema, relationships, ERD
   - docs/AUTHENTICATION, docs/NOTIFICATIONS, docs/XENFORO_OAUTH = subsystem-specific guides
   - docs/CHEAT_SHEET, docs/COMPOSER, docs/NPM, docs/VSCODE_EXTENSIONS = tooling and workflow references
   - docs/DEPLOY = deployment instructions for building out a new server, but leave the quick deploy instructions in the cheat_sheet

4. For any repeated information across files, keep the most complete version in the correct canonical document and replace the duplicates with short summaries + links.

5. Standardize tone, formatting, and section headings across all docs while preserving the project's Imperial voice.

6. Do not change the project's lore or personality. Only tighten, reorganize, and clarify.

Apply all edits directly to the files in the docs/ folder and update the README links accordingly.
```

## PHPSTAN

```
Resolve the PHPStan errors shown below using the following rules:

1. Make only the smallest, safest changes required to satisfy PHPStan.
2. Do NOT modify business logic, control flow, naming, architecture, or domain concepts.
3. Only fix issues that are purely mechanical, such as:
   - missing or incorrect type hints
   - missing or incorrect return types
   - missing or incorrect imports
   - incorrect or missing docblocks
   - incorrect or missing generics
   - incorrect interface or abstract method signatures
   - missing @property, @method, or @mixin annotations for Laravel/Larastan
   - incorrect collection or array shapes
4. Do NOT introduce new classes, traits, helpers, or abstractions.
5. Do NOT rewrite or “improve” code beyond what PHPStan requires.
6. If any error requires architectural or domain knowledge, STOP and ask me before making changes.
7. Apply all fixes directly to the affected files.

```