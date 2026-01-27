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
   - Update the corresponding feature tests so they match the refactored
     controller behavior and structure.
   - Fix any broken imports, route names, or expectations.
   - Ensure the tests reflect the new MagicBusController pattern if relevant.

3. Query & QueryHandler Unit Tests
   - For any Query or QueryHandler referenced by these controllers, ensure
     there are proper unit tests.
   - If tests exist, update them to match the current code.
   - If tests do not exist, create new unit tests following the project's
     existing testing conventions.
   - Do not modify the Query or Handler logic itself.

Constraints:
- Do not touch unrelated files.
- Do not introduce new dependencies.
- Keep all changes minimal, accurate, and aligned with the existing project style.

Apply all edits automatically.
```

## Update the Database Diagram

This prompt generates a complete DATABASE.md file, including table structures, column definitions, constraints, inferred relationships, and a Mermaid ER diagram visualizing table dependencies. The output provides an up‑to‑date, human‑readable reference for the database schema.

```
You are operating inside a Laravel project. Your task is to analyze *all* migration files in the workspace and produce a complete DATABASE.md file at the project root.

Requirements:

1. Scan every migration to determine:
   - All tables created
   - All columns and their types
   - Primary keys, unique constraints, indexes
   - Foreign keys and their referenced tables
   - Pivot tables and many-to-many relationships
   - Soft deletes, timestamps, morphs, and other Laravel helpers

2. Build a DATABASE.md file containing:
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
   - Do not modify any project files except creating/updating DATABASE.md

5. After generating the file, output the full contents of DATABASE.md so I can review it before saving.

Do not guess table structures beyond what migrations define. Infer relationships only when foreign keys or naming conventions clearly indicate them.

Begin by analyzing all migrations and then produce the complete DATABASE.md content.
```