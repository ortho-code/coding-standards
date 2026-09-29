# Changelog

What changed in each release, for the repositories that adopt this standard.

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
