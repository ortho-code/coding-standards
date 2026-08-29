# coding-standards

OrthoCode's coding standards, built on the [`ortho-code/standards-sync`](https://github.com/ortho-code/standards-sync) engine. This package declares *which* config files belong in an OrthoCode repository and *what* they contain; the engine understands the formats and writes them.

The standard splits by consumer kind, because a library and an application genuinely want different things — psalm's `findUnusedCode` is right for an application and noise for a library whose public API is uncalled by design.

- **`PackageStandard`** — for library packages.
- **`ProjectStandard`** — for applications.

A shared base is extracted once the overlap between them is visible, rather than designed up front.

CI is not part of either tier. It lives in a **forge companion** a consumer declares beside the tier — `ProjectGitHubStandard` or `ProjectBitbucketStandard` — because a tier that shipped one forge's CI could not be adopted on another: nothing in the engine enforces a file's *absence*, so the wrong workflow would arrive and could not be refused.

```php
return SyncConfig::create()
    ->withRuleSet(new ProjectStandard())
    ->withRuleSet(new ProjectBitbucketStandard());
```

## What `PackageStandard` ships

**Managed blocks**, distributed under the label `ortho-code`: `.editorconfig`, `.gitignore`, a `standards.yml` workflow running the checks on every push and pull request, and a tag-driven `release.yml` whose notes are the `CHANGELOG.md` section for that tag. On a repository's first sync each block is appended, keeping whatever the file already held; afterwards only the inside of the block is rewritten, so everything outside it stays the repository's own. The changelog itself is never synced — the standard ships the release mechanism, each repository writes its own prose.

**Shared config, imported rather than copied**, so it rides `composer update`: the ECS fixer set (PER-CS 3.0 plus ECS's prepared sets, single quotes always), the Rector set, and the PHPStan ruleset. Each is registered as one entry in the consumer's own config, which the consumer otherwise owns.

**Values a project cannot loosen.** PHPStan has a level floor of 6 and `treatPhpDocTypesAsCertain: false` rewritten on every sync. Psalm is seeded at `errorLevel="2"` with `findUnusedCode="false"`, and a looser level is tightened. PHPUnit is seeded and thirteen strictness flags are pinned, including the two PHPUnit already defaults to true, so the config states the whole contract rather than half of it. A seeded config is one-shot: it is written only where none exists and never edits an existing one — the template bootstraps, the pins converge.

**Renovate** extends the shared preset in this repository (`renovate-package-preset.json`), reached as `local>ortho-code/coding-standards:renovate-package-preset`.

**And the enforcement that makes it real**, since synced config enforces nothing on its own: the PHP version and every tool the standard configures are required in the consumer's `composer.json`, an `app-checks` script runs them all plus `standards-sync sync --check`, and the managed workflow calls that script. A repository that drifts fails its own CI rather than drifting quietly.

## What `ProjectStandard` ships

The same shape as the package tier, with its values set to **what an application can pass today** rather than where the standard is heading; every one of them has a ratchet in [`docs/roadmap.md`](docs/roadmap.md).

**Managed blocks** for `.editorconfig` and `.gitignore` — the same editor settings as the package tier, and the same ignore list minus `composer.lock`, which an application commits.

**Values a project cannot loosen.** PHPStan has a level floor of 9 with `treatPhpDocTypesAsCertain: false`; PHPUnit is seeded and eight strictness flags are pinned, the eight that exist in the PHPUnit version the tier requires. Rector's shared set is imported and targets the tier's own PHP floor, never a version ahead of it.

**Tools whose rule sets are still each repository's own**: `php-cs-fixer` and `phpcs` with `slevomat/coding-standard` are required and given `app-*` entry points, but no shared ruleset — the engine has no rule family that can carry one yet. `armin/editorconfig-cli` makes the synced `.editorconfig` enforced rather than advisory, configured entirely through its script's exclude flags.

**Not shipped, deliberately**: Psalm and ECS. Both are on the roadmap with the measured cost of adopting them.

**And the enforcement that makes it real**: an `app-checks` script that runs the sync check and every tool, which the forge companion's CI calls by name. `app-outdated` is declared but stays *outside* `app-checks`, so CI never fails on someone else's release cadence.

## Usage

Require the package as a dev dependency and keep a `standards-sync.php` at the repository root:

```php
return SyncConfig::create()->withRuleSet(new PackageStandard());
```

Then `vendor/bin/standards-sync sync` applies the standard, and `sync --check` reports drift without writing. Both are available as `composer app-sync` and `composer app-sync-check` once the standard has been applied.

## Development

Everything distributed lives under `templates/`, one subdirectory per tier; the configs this package lints *itself* with stay at its root, outside that directory. This package applies its own standard to itself — its `.editorconfig`, `.gitignore`, workflows and tool configs are all synced output, so `composer app-checks` here runs exactly what a consumer runs.

Tests: `composer app-run-tests`. They assert this package's contribution — the right content wired to the right file — not the engine's rendering, which the engine's own suite pins. Where the standard states the same thing twice, the suite pins the pair: a shipped template's values against the rule that enforces them, and the workflow's call against the script name the standard declares.

Authoring conventions are in [`docs/authoring.md`](docs/authoring.md), the reasoning behind what the standard ships in [`docs/decisions.md`](docs/decisions.md), and what is still open in [`docs/roadmap.md`](docs/roadmap.md); what the engine itself requires is in its own [authoring guide](https://github.com/ortho-code/standards-sync/blob/main/docs/authoring-org-packages.md).

## License

[MIT](LICENSE)
