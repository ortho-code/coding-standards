# coding-standards

OrthoCode's coding standards, built on the [`ortho-code/standards-sync`](https://github.com/ortho-code/standards-sync) engine. This package declares *which* config files belong in an OrthoCode repository and *what* they contain; the engine understands the formats and writes them.

The standard splits by consumer kind, because a library and an application genuinely want different things — psalm's `findUnusedCode` is right for an application and noise for a library whose public API is uncalled by design.

- **`PackageStandard`** — for library packages.
- **`ProjectStandard`** — for applications. Not built yet.

A shared base is extracted once both tiers exist and the overlap is visible, rather than designed up front.

## What `PackageStandard` ships

A managed `.editorconfig` block and a managed `.gitignore` block, distributed under the label `ortho-code`. On a repository's first sync each block is appended, keeping whatever the file already held; on later syncs only the inside of the block is rewritten, so everything outside it stays the repository's own.

More families follow one at a time — the toolchain and its check script, phpunit, ECS, Rector, PHPStan, Psalm, Renovate.

## Usage

Require the package as a dev dependency and keep a `standards-sync.php` at the repository root:

```php
return SyncConfig::create()->withRuleSet(new PackageStandard());
```

Then `vendor/bin/standards-sync sync` applies the standard, and `sync --check` reports drift without writing.

## Development

Everything distributed lives under `templates/`, one subdirectory per tier; the configs this package lints *itself* with stay at its root, outside that directory. This package syncs itself — its own `.editorconfig` and `.gitignore` carry the same managed blocks a consumer gets.

Tests: `composer app-run-tests`. They assert this package's contribution — the right content wired to the right file — not the engine's rendering, which the engine's own suite pins.

The engine resolves locally through a path repository (`../standards-sync`, symlinked). Authoring conventions are in [`docs/authoring.md`](docs/authoring.md); what the engine itself requires is in its own [authoring guide](https://github.com/ortho-code/standards-sync/blob/main/docs/authoring-org-packages.md).

## License

[MIT](LICENSE)
