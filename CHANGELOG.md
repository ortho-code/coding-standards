# Changelog

What changed in each release, for the repositories that adopt this standard.

## 0.1.0 — 2026-08-28

First release of the package tier. `PackageStandard` syncs a managed `.editorconfig` and `.gitignore`, seeds phpunit and psalm configs with values a project cannot loosen, registers the shared ECS and Rector sets and the shared PHPStan ruleset, extends the shared renovate preset, and makes all of it enforceable: the tools are required in the consumer's manifest, `app-checks` runs them, and a managed workflow calls that script.

Pre-release semantics: v0 minors may break.
