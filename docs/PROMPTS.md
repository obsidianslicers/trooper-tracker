# CODING COPILOT PROMPTS

## PHPDoc Comments

This prompt performs a structured audit of PHPDoc blocks within a specified directory. It reviews every class and its public methods, correcting and updating class‑level and method‑level documentation so it accurately reflects the existing code. It removes stale or incorrect annotations, avoids modifying signatures or logic, and focuses solely on improving clarity, correctness, and IDE type inference. The prompt applies recursively through subfolders, respects any excluded paths, and updates only the PHPDoc content while leaving all functional code untouched.

```
Audit and correct all PHPDoc comments for every class in [TARGET_PATH] and all subfolders.

Scope:
- All PHP classes in [TARGET_PATH]/**
- Include nested subfolders
- Only modify PHPDoc comments

Tasks:
- Ensure each class has accurate class-level PHPDoc describing its purpose and responsibilities.
- Ensure each public method has correct PHPDoc that matches the actual method signature (parameters, return types).
- Remove any incorrect, stale, or redundant annotations, including @var inside methods.
- Do NOT add or modify framework-specific annotations (routes, attributes, middleware, validation rules, etc.) unless explicitly requested.
- Do NOT change method signatures, namespaces, imports, or business logic.
- Only update PHPDoc blocks so they accurately reflect the code and improve IDE inference.

Apply this consistently across all files in [TARGET_PATH].
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

## Update PHPDoc Comments and Tests

This prompt audits and corrects PHPDoc across the selected controllers, updates their feature tests to match current behavior, and ensures every referenced Query and QueryHandler has proper unit test coverage—creating or adjusting tests as needed while keeping all changes scoped strictly to the files in context.

```
Work only with the files currently selected in the chat context, plus any
Query or QueryHandler classes referenced by them.

Perform the following tasks:

1. PHPDoc Audit (Controllers)
   - Review and correct all PHPDoc comments in the selected controller files.
   - Ensure class-level and method-level PHPDoc accurately describe the code.
   - Remove stale or incorrect annotations.
   - Do not modify method signatures, logic, or namespaces.

2. Controller Feature Tests
   - Update the corresponding feature tests so they match the refactored controller behavior and structure.
   - Fix any broken imports, route names, or expectations.
   - Ensure the tests reflect the new MagicBusController pattern if relevant.

3. Query & QueryHandler Unit Tests
   - For any Query or QueryHandler referenced by these controllers, ensure there are proper unit tests.
   - If tests exist, update them to match the current code.
   - If tests do not exist, create new unit tests following the project's existing testing conventions.
   - Do not modify the Query or Handler logic itself.

Constraints:
- Do not touch unrelated files.
- Do not introduce new dependencies.
- Keep all changes minimal, accurate, and aligned with the existing project style.

Apply all edits automatically.
```

## Update the Database Diagram

This prompt generates a complete docs/DATABASE.md file, including table structures, column definitions, constraints, inferred relationships, and a Mermaid ER diagram visualizing table dependencies. The output provides an up‑to‑date, human‑readable reference for the database schema.

```
You are operating inside a Laravel project. Your task is to analyze *all* migration files in the workspace and produce a complete docs/DATABASE.md file at the project root.

Requirements:

1. Scan every migration to determine:
   - All tables created
   - All columns and their types
   - Primary keys, unique constraints, indexes
   - Foreign keys and their referenced tables
   - Pivot tables and many-to-many relationships
   - Soft deletes, timestamps, morphs, and other Laravel helpers

2. Build a docs/DATABASE.md file containing:
   - A high-level overview of the database structure
   - A table-by-table breakdown with:
     - Table name
     - Purpose (infer from naming conventions)
     - Columns with types and constraints
     - Foreign keys and relationships
     - Notes on pivot tables or polymorphic relations

3. Include a Mermaid ER diagram showing table dependencies:
   - Use `erDiagram` syntax
   - Show relationships using correct cardinality
   - Include all foreign key links discovered in migrations

4. Formatting rules:
   - Use clean GitHub-flavored Markdown
   - Use headings, subheadings, and tables for clarity
   - Place the Mermaid diagram near the top under an “Entity Relationship Diagram” section
   - Do not modify any project files except creating/updating docs/DATABASE.md

5. After generating the file, output the full contents of docs/DATABASE.md so I can review it before saving.

Do not guess table structures beyond what migrations define. Infer relationships only when foreign keys or naming conventions clearly indicate them.

Begin by analyzing all migrations and then produce the complete docs/DATABASE.md content.
```

## Ensure all fileS use strong type checks

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