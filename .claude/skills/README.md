# Claude Code skills

Shared [skills](https://docs.claude.com/en/docs/claude-code/skills) for working on this repo.
Claude Code finds each `<name>/SKILL.md` here and uses it when a task matches its description;
you can also run one as `/<name>`.

| Skill | Use |
|---|---|
| [`verify-changes`](verify-changes/SKILL.md) | Check a change before calling it done: the tests that cover it, then the gates CI runs |

To add one, create `<name>/SKILL.md` with `name` and `description` frontmatter. Keep skills free of
anything specific to one machine or garrison. For a personal skill, use `~/.claude/skills/`, or
list its folder in `.git/info/exclude`.
