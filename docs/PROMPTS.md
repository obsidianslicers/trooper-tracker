# CODING COPILOT PROMPTS

## Task Prompt

```
@workspace [Describe Task, referencing target files like `#file:app/components/Header.svelte` and folder scope `app/components/*`]

### Guidelines to Follow:
- **Project Rules:** Strictly adhere to the standards, patterns, and guidelines specified in `#file:claude.md` and `#file:codex.md`.
- **Local Context:** Review neighbor files in the same directory near the task implementation to ensure seamless integration, consistent style, and matching imports/exports.
- **Output Requirements:**
  - Produce clean, well-typed code matching the architectural decisions in `claude.md` and `codex.md`.
  - Maintain consistent exception handling and pattern structure established across neighboring files.
```