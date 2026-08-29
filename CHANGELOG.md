# Changelog

What changed in each release, for the repositories that adopt this standard.

## 0.2.0 — 2026-08-29

Adds the application tier. `ProjectStandard` syncs the same managed `.editorconfig` as the package tier and a `.gitignore` that leaves `composer.lock` tracked, seeds a phpunit config and pins eight strictness flags, imports a shared PHPStan ruleset at level 9 and a shared Rector set targeting the tier's own PHP floor, and requires php-cs-fixer, phpcs and editorconfig-cli with an `app-*` entry point each. It ships neither Psalm nor ECS.

CI is no longer part of a tier. It moves to a forge companion a consumer declares beside one — `ProjectGitHubStandard` or `ProjectBitbucketStandard` — because nothing removes a file, so a tier carrying one forge's CI cannot be adopted on another. `PackageStandard` is unchanged and still ships its own workflows.

Every package-tier consumer now also gains `roave/security-advisories` at `dev-latest` on its next sync.

Pre-release semantics: v0 minors may break.

## 0.1.0 — 2026-08-28

First release of the package tier. `PackageStandard` syncs a managed `.editorconfig` and `.gitignore`, seeds phpunit and psalm configs with values a project cannot loosen, registers the shared ECS and Rector sets and the shared PHPStan ruleset, extends the shared renovate preset, and makes all of it enforceable: the tools are required in the consumer's manifest, `app-checks` runs them, and a managed workflow calls that script.

Pre-release semantics: v0 minors may break.
