# coding-standards — the OrthoCode standard

OrthoCode's own standards package on top of the `ortho-code/standards-sync` engine: it declares which config files belong in an OrthoCode repository and what they contain.
Split by consumer kind — `PackageStandard` for libraries, `ProjectStandard` for applications later, a shared base extracted once both exist.

**This repository is published.** Nothing in it names an employer, a client, another organisation's packages, a local filesystem path, or any Claude config; examples speak of "a consumer" and "an org".
Keep this file project-facing (no personal workflow prefs).

## Where things are documented

- [README.md](README.md) — what the package is and ships, usage, development notes.
- [docs/authoring.md](docs/authoring.md) — how we author the standards here: class placement, structuring by tool family, values inline, what we test.
- The engine's own docs, for what the engine requires rather than what we chose: `docs/authoring-org-packages.md` and `docs/conventions.md` in `ortho-code/standards-sync`.

## Working rules

- **References are one-way.** This file may point at the README, docs and code; repo files never reference Claude config as the home of knowledge.
- Values stay inline literals where the maintainer reads and edits them — the deliberate inverse of the engine's constants-over-literals convention. See [docs/authoring.md](docs/authoring.md).
- A standard's rules are declared in the order the engine's declaration-order semantics require: a tool's import or template rule before its value rules.
- This package syncs itself; its root `.editorconfig` and `.gitignore` are synced output, not hand-written.
- Validate with `composer app-run-tests` after changes.
