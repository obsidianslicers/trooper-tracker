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