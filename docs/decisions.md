# Decision record

Why the standard is shaped the way it is, and what each decision rejected, so a later reader does not re-open a settled question.
What the standard ships today is in the [README](../README.md), and how it is written is in [authoring.md](authoring.md); this file is the reasoning behind both.
Entries are dated and append-only — a later entry supersedes an earlier one rather than rewriting it.

Every number below was measured against the engine repository ([`ortho-code/standards-sync`](https://github.com/ortho-code/standards-sync)), which is this standard's first consumer and the reason the measurements exist: a standard nothing consumes can never be caught being wrong.

## Agreed 2026-08-28 — the package tier

`PackageStandard` was designed and built on one date, family by family, each family landing its template, its pins, its tool requirement and its script together so that every step left the standard true and passing.
The sections below are that record, by topic.

### Split by consumer kind, in one composer package

A library and an application genuinely want different things, so the standard splits into tiers: `PackageStandard` now, `ProjectStandard` later, and a shared `OrthoCodeStandard` **extracted** once both exist and the overlap is visible rather than designed up front.
Psalm's `findUnusedCode` is the clearest case — right for an application, noise for a library whose public API is uncalled from the inside by design.

All tiers live in one package under the namespace root, so a consumer writes `new PackageStandard()`.
*Rejected*: one composer package per tier — it buys nothing and costs a release line and a version constraint each.

The mechanism is the engine's `include()`, which takes any `Standard` instance, so tiers compose inside one package; templates separate by subdirectory (`$package->read('package/.editorconfig')`) because a distributed path is joined relative to the package.
Both cases are proven: cross-package `include()` by the engine's fixtures, and the same-package case by the engine's own `Authoring` tests since 2026-08-29, which is also when its authoring guide gained a section on the shape.
The including tier passes its own `$package` down — `$this->include(new OrthoCodeStandard($package))`.
Left to self-locate, an included tier resolves the same package in production but renders bare paths under a test that injects a fixed `Package`, while the outer tier renders `vendor/…`.

### The marker label is the vendor name

Every managed block the standard owns carries the label `ortho-code`.
*Rejected*: `ortho-code-coding-standards`, which the package-name convention would give — too long in a marker line, and the vendor name alone is unambiguous while leaving room for a tier-specific label later.

### Public, published early

The package went public and reached a `0.1.0` tag as soon as the first tier was real, and that decides other things rather than being a preference.
The engine is public, so a private standard could not be a dev dependency of it — every outside contributor's `composer install` would fail — and the engine's synced configs, which name `vendor/ortho-code/coding-standards/templates/…`, could not be committed either.
The consequence held throughout the build: adoption by the first consumer stayed uncommitted until the package was published, synced into a scratch copy and read as a diff instead.

*A trap worth stating once*: a repository that consumes a standard which in turn requires that repository's own package cannot install it, because the root package is `dev-main` and does not match a release constraint.
Composer reports its own `dep-on-root` link on the failure.
The fix is `extra.branch-alias` on the root, kept in step with its release line, and it is needed only while that repository consumes the standard.

### The package depends on the published engine

`"ortho-code/standards-sync": "^0.2"`, with no `repositories` entry.
Three reasons: composer honours `repositories` only from the root package, so a path repository would ship to Packagist as dead metadata; a published package requiring `dev-main` forces every consumer to allow dev stability for the engine; and building against the released artifact is what a real consumer experiences.
*Rejected*: the initial `dev-main` plus path repository, which is right for a repository that genuinely co-develops with the engine and wrong for one that should feel what it ships.

### MIT, matching the engine

The package is config templates plus a thin declaration class, and its second job is to be what another organisation copies as a starting point; MIT removes every obstacle to that and is what consumers expect from composer metadata.
*Rejected*: CC0-1.0 — defensible for barely-copyrightable config, but unusual in composer metadata, distrusted by some corporate policies, and the parts that clearly are copyrightable (the standard classes, the prose) are what MIT covers cleanly.
Apache-2.0 is heavier than anything here needs, and `proprietary` contradicts publishing.

### Measure first, then declare; never a committed baseline

For the mechanical tools — ECS and Rector — the target is declared and the code is fixed (`ecs check --fix`, `rector process`).
For the analysers the floor is **measured before it is written**: run the tool against a real consumer, read the numbers, then declare the floor and record the next rung as a ratchet.
*Rejected*: committed baseline files — they are a per-repository artefact a shared standard cannot manage, and they rot silently.

### `.editorconfig`: small, with `insert_final_newline = true`

The shipped block is eight settings, an `[*.md]` exception for trailing whitespace, and two-space indentation for `json`/`yaml`/`yml`/`neon`.
An IDE-exported `.editorconfig` of several hundred lines is what repositories tend to carry; the standard deliberately ships a file a person can read.

`max_line_length` is absent on purpose: a large value is a non-constraint, and any real value fights writing prose one sentence per line.

The global `insert_final_newline` is `true`, which inverts what existing repositories declared, and the `[*.php]` override disappears as redundant.
POSIX defines a line as ending in a newline, git and diff both mark its absence, and that marker becomes noise in every diff that touches the last line; PSR-12 restates it for PHP.
*Evidence*: of 442 tracked non-empty files in the first consumer, exactly **3** lacked a final newline — and they were precisely the files the old `false` applied to. The declaration described 3 files and misdescribed 439.

### `.gitignore` differs per tier, and that is the point

The package tier ignores `/vendor/`, `/.idea/`, `/.phpunit.cache/`, `/.phpunit.result.cache`, `/.deptrac.cache`, `.DS_Store` and `/composer.lock`.
The project tier will ship the same list minus the lock, which applications commit.
A per-tier difference in one small file is the cheapest possible demonstration that the tier split earns its keep.

### Toolchain shape: one script per tool, plus an aggregate

Each family declares its own `app-*` script, and `app-checks` composes them with `@`-references: one command per tool while debugging, one command to run everything.
The aggregate leads with `app-sync-check`, so a repository that has drifted from the standard fails before any tool runs.
*Rejected*: a single flat check script — it makes debugging one tool a matter of editing the script.
*Rejected*: `composer outdated --strict` among the checks — it fails on other people's release cadence rather than on anything the repository did.

### PHPUnit: thirteen strictness flags, defaults included

`beStrictAboutChangesToGlobalState`, `beStrictAboutOutputDuringTests`, `beStrictAboutTestsThatDoNotTestAnything`, `failOnDeprecation`, `failOnEmptyTestSuite`, `failOnIncomplete`, `failOnNotice`, `failOnPhpunitDeprecation`, `failOnPhpunitNotice`, `failOnPhpunitWarning`, `failOnRisky`, `failOnSkipped`, `failOnWarning`.
Two of them already default to true; they are pinned anyway so the synced config states the whole contract rather than half of it, and so a project cannot quietly turn them off.
All thirteen exist in both PHPUnit 12 and 13 (both schemas read), which is what makes `^12` a floor rather than a cap — a consumer already on 13 keeps 13.

*Excluded, with reason*: `failOnAllIssues`, which is blunt and subsumes every current and future issue category; and `beStrictAboutCoverageMetadata`, which would require coverage metadata on every test — a separate decision from strictness.

### ECS: PER-CS 3.0, pinned rather than aliased

The set is `withPhpCsFixerSets(perCS30: true)` plus all seven prepared sets, `DeclareStrictTypesFixer`, `FinalClassFixer`, and `SingleQuoteFixer` configured with `strings_containing_single_quote_chars`.
The version is pinned because the `@PER-CS` alias resolves to the newest PER set, which would change the standard under every consumer on a php-cs-fixer release.
PER-CS 1.0 *is* `@PSR12`, so this is a strict superset of the obvious starting point; it also brings `new_with_parentheses` with `anonymous_class => false` and `array_indentation` for free, which is why both were dropped from the explicit configuration they first had.

**Single quotes always**: a string holding an apostrophe escapes it rather than switching to double quotes, and where escaping hurts readability the answer is `sprintf()`.
*Rejected*: `SingleQuoteFixer` at its default, which leaves apostrophe-bearing strings double-quoted — the opposite of the convention.

*Skipped*: `LineLengthFixer`, which hard-wraps prose written one sentence per line, and `PhpdocLineSpanFixer`, which forces every docblock multi-line.
Together they were 96 of 193 findings on the first consumer; without them the set cost 97, all auto-fixable.

⚠ Adopting this set means a repository whose own recorded convention is "single quotes unless interpolation or escapes need double" is changing that recorded line, which deserves its own agreement rather than being overwritten by a formatter.

### Rector: seven sets, with skips that each defend something

`CODE_QUALITY`, `CODING_STYLE`, `DEAD_CODE`, `EARLY_RETURN`, `INSTANCEOF`, `TYPE_DECLARATION` and `PHP_85`, plus `ClassPropertyAssignToConstructorPromotionRector` and `DeclareStrictTypesRector`, with `importNames`, `importShortClasses(false)`, `removeUnusedImports` and `reportUnusedSkips`.
Cost on the first consumer: 18 files.

Every skip has its own reason, and they are worth keeping distinct:
`NarrowWideUnionReturnTypeRector` would narrow a deliberately wide return type, which is a contract rather than an oversight;
`LocallyCalledStaticMethodToNonStaticRector`, because stateless private helpers are static on purpose;
`SortCallLikeNamedArgsRector`, because argument order is the author's;
`CatchExceptionNameMatchingTypeRector`, because catch variables are named for their use;
`SimplifyQuoteEscapeRector`, because it contradicts single-quotes-always;
and three blank-line rules that are ECS's territory.

*Not adopted*: `NAMING`, whose rename rules are more disruptive than useful, and `PRIVATIZATION`, which privatizes public API that nothing calls internally — the same library hazard psalm's `findUnusedCode` has.
A project tier may take a different view of the second one.

### `withEditorConfig()` is adopted, and it cost the engine a release

ECS reads the consumer's own `.editorconfig` for indentation and line endings, so one shared set can serve a spaces repository and a tabs repository, each governed by its own file.
It parses that file with `parse_ini_string()`, which fails on parentheses — and the engine's marker at the time read `# >>> label (managed) >>>`.
Probed precisely: `# >>> label >>>` parses, `# (managed)` does not, and ordinary `#` comments are fine.

Rather than lose the feature, the engine's marker grammar changed to `- managed` in its 0.2.0, and this package requires `^0.2`.
Verified end to end against the published engine: ECS parses the managed `.editorconfig` and runs green, and flipping `indent_style` to `tab` in it makes `IndentationTypeFixer` fire — so the setting is live, not silently ignored.
The migration cost was a hand pass over every already-synced repository, which is the price of changing a rendered form after it has shipped.

### PHPStan: floor 6, measured

Analysing the first consumer's `src` with `treatPhpDocTypesAsCertain: false`: **1 error at level 4 and at level 6, 24 at level 8, 29 at level 9**.
The single finding at 6 was real, so 6 is one fix from clean and is declared as the floor; level 8 costs 23 more and level 9 costs 28, recorded as the ratchet.

*The finding set aside to reach those numbers*: eleven of the twelve raw errors at level 4 were `return.unusedType` — "the method never returns null, so null can be removed from the return type" — against a contract where the wide type is the point.
`checkTooWideReturnTypesInProtectedAndPublicMethods: false` is accepted by PHPStan 2.2 but does not govern that check; the working lever is `ignoreErrors` on the identifier.

**The shared ruleset stays neutral and the consumer carries the suppression.**
A suppression belongs where the contract that justifies it lives, and a shared ruleset should not blanket-disable a useful check for every consumer because one of them has a deliberate contract.

The ruleset is imported natively rather than copied, so it rides `composer update`, and the package's own suite pins the shipped ruleset's level against the floor the standard enforces.

### Psalm: errorLevel 2, unused code off, `#[\Override]` adopted

`findUnusedCode` **defaults to true** in Psalm 6, so omitting it is not enough — the template sets it `false` explicitly.
Left on, it reports the first consumer's entire authoring API as unused: the classes and methods that exist for consumers Psalm cannot see.

*Measured on that consumer's `src` with unused code off*: errorLevel 1 → 114 errors, 2 → 92, 4 → 82.
**69 of those were `MissingOverrideAttribute` at every level**, so the real remainder was 45 / 23 / 13.
The floor is errorLevel 2 — numerically a ceiling, since Psalm's scale is inverted — with errorLevel 1 recorded as the ratchet, 22 findings away.

`#[\Override]` is **adopted rather than suppressed**: it is auto-fixable in one sweep (`psalm --alter --issues=MissingOverrideAttribute`), it is what Psalm and Rector both want, and suppressing it in a *shared* template would impose one codebase's reasoning on every future package.
It reverses a preference some existing repositories hold, deliberately.

### CI: a separate workflow the standard owns

The standard ships `.github/workflows/standards.yml` as a managed block running `composer app-checks` on the declared PHP version.
*Rejected*: a block inside a consumer's existing CI workflow — the content would have to carry exact `jobs:`-level indentation as literal text and assume every consumer's workflow has the same shape.
The consequence for a consumer is that its own workflow keeps only what `app-checks` does not cover.

`ComposerRequirement('php', '^8.5', Runtime)` landed with it: the workflow hardcodes a PHP version, so the manifest states the same floor, and the two are raised together.

### Release: the mechanism is shared, the prose is not

`.github/workflows/release.yml` is a managed block — tag-driven, with the release notes extracted from the `CHANGELOG.md` section for that tag, failing loudly when the tag has no section.
The changelog itself is never synced.
It is human prose written for the people consuming the package, a managed block would have to carry markers through it, and the engine's one-shot seeds are all tool-specific.
Seeding it generically is [on the roadmap](roadmap.md), not in the standard.

### Renovate: a preset in this repository, named for its tier

The preset lives at the repository root as `renovate-package-preset.json` and is referenced as `local>ortho-code/coding-standards:renovate-package-preset`.
It is at the root rather than under `templates/` because the bot fetches it over the forge API and never from a composer install — it is not distributed content in the engine's sense.

A bare `local><owner>/<repo>` reference would resolve `default.json`, which was the first shape.
The tier split decides against it: `default.json` can only be one preset, and this repository will serve a project-tier preset too, so the presets need names and the tier belongs in the name.

⚠ *The rename demonstrated the written-output invariant within hours of it being recorded.*
Changing the preset reference did not update this repository's own `renovate.json5`: it left the old entry and appended the new one beside it, because recognition matches the current rendering and nothing retracts.
It was fixed by hand here, and every consumer would need the same manual step.
**Do not rename a rendered value once it has shipped** — the same applies to the import entries, the marker grammar, and the template paths rendered inside them.

### What the first consumer's adoption actually cost

The bill came in as measured: ECS 97 findings, Rector 17 files, Psalm 23 after the 69-attribute sweep, PHPStan 1 plus the scoped `return.unusedType` ignore.
Two Psalm findings were real rather than notational, which is the argument for the analysers paying for themselves.

One fact surfaced that no measurement predicted: **a package that ships a tool must link its own binary.**
Composer's bin directory holds *dependencies'* binaries and never the root package's own, so a repository that ships the very tool the standard's `sync --check` script calls cannot find it by name.
A `post-install-cmd` linking the binary fixes it, and the engine's authoring guide records the general form.
