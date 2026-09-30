# Changelog

What changed in each release, for the repositories that adopt this standard.

## Unreleased

The shared Renovate preset now waits until a release is seven days old before proposing it, opens a pull request when Renovate renames an option your config uses, and no longer proposes changes to your `php` requirement, which the standard owns.
A preset change reaches your repository on the bot's next run, ahead of any release of this package.

## 0.3.2 — 2026-09-30

`PackageStandard` now requires PHPUnit `^13`, raised in your `composer.json` on the next sync; run `composer update phpunit/phpunit` afterwards if your lock still holds 12.
The synced `phpunit.xml` and its thirteen strictness flags are unchanged and valid under 13, but since the standard pins `failOnPhpunitDeprecation`, a test using an API that PHPUnit 13 deprecated now fails.

## 0.3.1 — 2026-09-30

`PackageStandard` now syncs a managed `.gitattributes` block that export-ignores what an installed copy of a library never needs: every file the standard syncs, under every name the engine accepts for it, `standards-sync.php` and `standards-sync.lock`, `tests/`, and a coding agent's project files — the README lists them all.
The first `sync --check` after upgrading fails until you run `sync` once and commit the `.gitattributes` it writes.
The block is appended to an existing `.gitattributes`, so export-ignore lines you already had that it now duplicates can go, and an override you already had (`/<path> -export-ignore`) has to move below the block, since git applies the later line.
Anything else your library should leave out of its installs stays a line of your own below the block, such as `/docs export-ignore`.
This package's own installs now carry only `src/`, `templates/`, the manifest, the licence, the readme and this changelog.

The GitHub workflows of `PackageStandard` and `ProjectGitHubStandard` now run on `ubuntu-26.04` instead of `ubuntu-latest`, with `actions/checkout@v7` and `ramsey/composer-install@v4`, so no action in them runs on the deprecated Node.js 20.
The first `sync --check` after upgrading fails until you run `sync` once and commit the workflows it rewrites.
The Bitbucket pipeline is unchanged.

## 0.3.0 — 2026-09-29

**Requires `ortho-code/standards-sync` 0.3.** A composer script the standard declares now keeps any command your repository added to it, and a later sync removes a command the standard stops declaring.
The engine records what the standard declared in `standards-sync.lock`, which you commit beside `standards-sync.php`; the first `sync --check` after upgrading fails until you run `sync` once and commit the lock.
**Adopting over scripts you already have:** your own command stays after the declared one, so a tool can run twice — after the first sync, delete your own line wherever it runs the same tool; the drift report names every line it kept.
`app-outdated` still stays out of the standard's `app-checks`, and a repository whose own `app-checks` runs it keeps that step.
A standard declared beside this one can now add a step to its `app-checks` without restating the list.

## 0.2.2 — 2026-09-29

**0.2.1 changed your code style, although its notes said it would not.** ECS 13.3 loads its PER-CS set as one of the prepared sets, and the sets loaded after it reset some of PER-CS's options: under 0.2.1, imports are sorted alphabetically, an anonymous class gains parentheses (`new class() extends`) and loses the space before its arguments, and empty bodies are relaid.
0.2.2 loads PER-CS after the other sets, which restores exactly the style 0.2.0 had.
If you ran `ecs check --fix` on 0.2.1, running it again on 0.2.2 undoes the parentheses, spacing and body changes; the alphabetical import order stays, because PER-CS keeps imports in whatever order it finds them, so revert that part by hand if you want your old order back — either order passes.

## 0.2.1 — 2026-09-29

The package tier's shared ECS set works with ECS 13.3, which removed two things it relied on: passing `perCS30:` to `withPhpCsFixerSets()` became a fatal error, and `withEditorConfig()` stopped reading anything.
PER-CS 3.0 now comes from ECS's own `perCs` prepared set, the same 62 rules with the same options, so your code style does not move.
The standard now requires ECS `^13.3`, raised in your `composer.json` on the next sync; run `composer update symplify/easy-coding-standard` afterwards if your lock still holds an older version.
Indentation and line endings are now stated in the set as four spaces and LF, which is what the synced `.editorconfig` declares; a repository indenting with tabs adds `->withSpacing(indentation: Option::INDENTATION_TAB)` to its own `ecs.php`.

## 0.2.0 — 2026-08-29

Adds the application tier. `ProjectStandard` syncs the same managed `.editorconfig` as the package tier and a `.gitignore` that leaves `composer.lock` tracked, seeds a phpunit config and pins eight strictness flags, imports a shared PHPStan ruleset at level 9 and a shared Rector set targeting the tier's own PHP floor, and requires php-cs-fixer, phpcs and editorconfig-cli with an `app-*` entry point each. It ships neither Psalm nor ECS.

CI is no longer part of a tier. It moves to a forge companion a consumer declares beside one — `ProjectGitHubStandard` or `ProjectBitbucketStandard` — because nothing removes a file, so a tier carrying one forge's CI cannot be adopted on another. `PackageStandard` is unchanged and still ships its own workflows.

Every package-tier consumer now also gains `roave/security-advisories` at `dev-latest` on its next sync.

Pre-release semantics: v0 minors may break.

## 0.1.0 — 2026-08-28

First release of the package tier. `PackageStandard` syncs a managed `.editorconfig` and `.gitignore`, seeds phpunit and psalm configs with values a project cannot loosen, registers the shared ECS and Rector sets and the shared PHPStan ruleset, extends the shared renovate preset, and makes all of it enforceable: the tools are required in the consumer's manifest, `app-checks` runs them, and a managed workflow calls that script.

Pre-release semantics: v0 minors may break.
