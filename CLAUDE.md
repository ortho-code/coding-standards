# coding-standards — the OrthoCode standard

OrthoCode's own standards package on top of the `ortho-code/standards-sync` engine: it declares which config files belong in an OrthoCode repository and what they contain.
Split by consumer kind — `PackageStandard` for libraries, `ProjectStandard` for applications — with a shared base extracted once the overlap is visible.
CI is a forge companion beside the tier (`ProjectGitHubStandard`, `ProjectBitbucketStandard`), because a tier that ships one forge's CI cannot be adopted on another: the engine has no rule that enforces a file's absence.

**This repository is published.** Nothing in it names an employer, a client, another organisation's packages, a local filesystem path, or any Claude config; examples speak of "a consumer" and "an org".
Keep this file project-facing (no personal workflow prefs).

## Where things are documented

- [README.md](README.md) — what the package is and ships, usage, development notes.
- [docs/authoring.md](docs/authoring.md) — how we author the standards here: class placement, structuring by tool family, values inline, what we test.
- [docs/decisions.md](docs/decisions.md) — why the standard is shaped this way, with what each decision rejected and the numbers it was measured against. **Read it before changing a shipped value; propose and get agreement before reversing a recorded decision.**
- [docs/roadmap.md](docs/roadmap.md) — what is still open, each item with its trigger.
- The engine's own docs, for what the engine requires rather than what we chose: `docs/authoring-org-packages.md` and `docs/conventions.md` in `ortho-code/standards-sync`.

## Working rules

- **References are one-way.** This file may point at the README, docs and code; repo files never reference Claude config as the home of knowledge.
- Values stay inline literals where the maintainer reads and edits them — the deliberate inverse of the engine's constants-over-literals convention. See [docs/authoring.md](docs/authoring.md).
- A standard's rules are declared in the order the engine's declaration-order semantics require: a tool's import or template rule before its value rules.
- This package applies its own standard to itself: its `.editorconfig`, `.gitignore`, tool configs and both workflows are synced output, not hand-written. `CHANGELOG.md` is the exception — the standard ships the release mechanism, never the prose.
- Validate with `composer app-checks` after changes: it runs the sync check, ECS, PHPStan, Psalm, Rector and the tests, which is exactly what a consumer runs.
