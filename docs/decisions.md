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

## Agreed 2026-08-29 — the application tier

`ProjectStandard` was designed against a real application rather than in the abstract, and every number below was measured against that consumer's source with the tools' target versions, in a throwaway harness that never modified it.
The consumer is an existing Symfony application on Bitbucket; it is not named here, since this repository is published and that one is not.

### Declared at what a consumer can pass today, with the ratchet recorded

The package tier was measured and then declared near the strict end, because its consumer was already there.
The application tier is declared at **what its first consumer can pass now**, and every gap to where the standard is heading is written into the [roadmap](roadmap.md) as a ratchet with its measured cost.
The reasoning is that a tier nothing can adopt enforces nothing: the application needs a dependency upgrade before it can reach the package tier's values, and a standard that waits for that upgrade is a standard with no consumer for months.

*Rejected*: declaring the package tier's values and letting the application fail every check until it catches up — indistinguishable, day to day, from having no standard.
*Rejected*: a committed baseline to bridge the gap, which the [2026-08-28 entry](#measure-first-then-declare-never-a-committed-baseline) already refuses for the same reasons.

### CI is a forge companion, not part of the tier

`ProjectStandard` carries no CI. `ProjectGitHubStandard` and `ProjectBitbucketStandard` each carry exactly one managed block, and a consumer declares one of them beside the tier.

This is forced rather than chosen. **The engine ships no rule that enforces a file's absence** — nothing in `src/Rules` deletes, and its own history records the removal case as never exercised — so a tier that shipped `.github/workflows/standards.yml` would hand a Bitbucket application a workflow it could neither run nor refuse.
Composition is the engine's `SyncConfig::withRuleSet()`, which is additive and folds every rule set's rules in declaration order.

*Rejected*: a `forge:` constructor parameter on the tier — one composition point, but the tier grows conditionals and a subclass adding parameters must still thread `?Package` through `Standard::__construct`.
*Rejected*: naming the companions forge-first (`GitHubProjectStandard`) — tier-first sorts the tiers together and makes a mismatched pair visible in the consumer's own config, which is the only place the mistake can be caught: nothing detects `ProjectStandard` declared beside a *package* forge companion.

⚠ The composition is real but untested territory: across the whole engine suite and its generated docs there are 86 `withRuleSet` call sites and **not one** chains two. It is supported by construction, not by a scenario.

### The measured cost, and what it bought

Against the consumer's `bin`, `config`, `public`, `src` and `tests`, with its own suppressions and exclusions removed so nothing hid:

| Tool | Finding |
|---|---|
| ECS, the package tier's shared set | 50 files, every finding auto-fixable |
| Rector, the package tier's shared set | 6 files — 5 of them only `declare(strict_types=1)` on framework bootstrap files |
| PHPStan | **6 errors, identical at levels 6, 8 and 9** |
| Psalm, errorLevel 2 | 147 across 40 files |
| Psalm, errorLevel 6 or 8 | **31, all `MissingOverrideAttribute`** |

PHPStan's six were *already* the consumer's own suppressions and exclusions, so adopting the analyser at any level up to 9 costs nothing there.
That is why the floor is 9 — but the evidence is circular and worth flagging: the application clears level 9 because it already runs level 9, and one such data point cannot say what the tier should demand of the next application.

### PHPUnit: eight flags, and a template written for two schemas

The tier requires `^9.6` and pins eight of the thirteen strictness flags the package tier pins.
The other five — `failOnDeprecation`, `failOnNotice`, `failOnPhpunitDeprecation`, `failOnPhpunitNotice`, `failOnPhpunitWarning` — **do not exist in the 9.6 schema** (read from its shipped `phpunit.xsd`), and pinning an attribute the schema lacks makes PHPUnit's own config validation complain.

The seeded template contains only elements valid in **both** PHPUnit 9 and 12: no `cacheDirectory`, no `<source>`, no `<coverage>`.
It therefore survives the version ratchet instead of being replaced by it, which is the point — a one-shot seed is never edited again once a consumer has one.

### Psalm is not shipped, and `findUnusedCode` is reversed

The application does not use Psalm, and the tier does not add it. The measured floor it could adopt — errorLevel 6, reachable with a single `psalm --alter --issues=MissingOverrideAttribute` sweep — is recorded in the roadmap so the cost is known when it is picked up.

More importantly, this **supersedes the `findUnusedCode` reasoning in the [2026-08-28 entry](#split-by-consumer-kind-in-one-composer-package)**, which made it the headline example of why the tiers split: right for an application, noise for a library.
Measured, that is wrong. Turning it on adds 60 findings, and **all 20 `UnusedClass` entries are framework- or test-runner-instantiated** — 10 test classes, 5 controllers, 3 template extensions, 1 event subscriber, 1 constraint validator. Not one is real dead code.
The library rationale — public API uncalled from inside — has an exact analogue in a dependency-injected application, where the container is the caller Psalm cannot see.
It could only be turned on together with a framework-aware plugin, which belongs to a framework standard rather than to a tier.

The tier split still earns its keep; the lock file, the PHP floor, the analyser set and the CI shape all differ. It is only this one example that did not survive contact with a real application.

### ECS is not shipped either, and the fixer families are the reason

The application uses `php-cs-fixer` plus `phpcs` with about fifty-five `slevomat` sniffs, and ECS runs both — so the migration is possible, but it is a migration.
Adhering to what the consumer has means requiring those two tools and declaring their `app-*` entry points, while **their rule sets stay each repository's own**: nothing in the engine can carry a shared `php-cs-fixer` or `phpcs` ruleset, and those are two new rule families.

The honest limit of that: a tool required and run with no shared configuration is enforcement-shaped without enforcing anything shared. It is a step, recorded as one.

### `armin/editorconfig-cli` needs no config file, measured

The consumer runs `ec` through a Symfony Finder config. It does not need one: `ec -e vendor -e node_modules -e var` produces byte-identical output to that finder config, because `-e` matches a directory *name* at any depth.

Bare `ec` is **not** equivalent — it reports 4 issues under a compiled asset tree, because `ec` excludes what `.gitignore` excludes and that tree is committed. So the excludes are load-bearing, and they fit in the script.
The family is therefore a requirement plus two scripts, with no template and no new engine rule.

### `app-outdated` is declared, and deliberately not aggregated

The [2026-08-28 entry](#toolchain-shape-one-script-per-tool-plus-an-aggregate) rejected `composer outdated --strict` *among the checks*, because it fails on other people's release cadence.
That rejection is about the aggregate, not the command: the tier declares `app-outdated` as a standalone script a developer can run, and keeps it out of `app-checks`.
An extended aggregate that includes it is left for when a second such script exists.

### `roave/security-advisories` is settled in both tiers

Recorded here because the roadmap carried it as open long after it stopped being so.
It was originally dropped because `ComposerRequirement` refused a branch constraint and that package publishes only `dev-master` and `dev-latest`; the engine removed that refusal on 2026-08-29, pinning a constraint that names only a branch, since branches have no ordering to floor.
Both tiers declare it at `dev-latest`, and an explicit dev constraint needs no stability flag of its own.

### The first collision between two standards, and it is silent

`ComposerScript::apply()` writes the named script's command list, replacing it.
Two rule sets declaring the same script name therefore do **not** merge: declaration order decides, the later one wins wholesale, the earlier one's commands vanish, and nothing detects or reports it.

The engine's designed answer is in that rule's own docblock — a project needing extra steps declares its own script and calls the owned one by `@name`. That works for a *project*. It does not work for a *standard*: a framework standard wanting one more step in `app-checks` cannot rename the entry point every consumer's CI calls.

The engine records the same shape for managed blocks, with directions and a trigger. It does not record the composer-script form, nor the two-standards-one-name case. Both belong in the engine's roadmap, and a framework standard is their trigger.

## Changed 2026-09-29 — ECS 13.3 moved PER-CS into a prepared set

### PER-CS 3.0 now comes from `withPreparedSets(perCs: true)`, and the ECS constraint carries the pin

This supersedes the mechanism in the [2026-08-28 entry](#ecs-per-cs-30-pinned-rather-than-aliased); the goal — PER-CS 3.0, never a moving alias — is unchanged.

ECS 13.3.0 emptied `withPhpCsFixerSets()`: it takes no parameters, only prints a deprecation warning, and passing `perCS30:` is a fatal "Unknown named parameter".
Every package-tier consumer broke on its first install to resolve 13.3; 13.2.19 was the last version to accept the parameter.
Its replacement is ECS's own prepared set `perCs`, loaded from its `config/set/per-cs.php`, which ECS 13.2 does not have — hence the floor moving from `^13.2` to `^13.3`.

*Verified before adopting it* (ECS 13.3.2, with the php-cs-fixer 3.95.24 it bundles): the set's 62 fixers are exactly the 62 rules php-cs-fixer resolves `@PER-CS3.0` to, and the 15 it configures carry exactly `@PER-CS3.0`'s options.
Applied to this repository, the new set produced no findings, so the style did not move.

**What changed about the pin.** The set name `perCs` carries no version, so the pin moved from php-cs-fixer's versioned set name to the ECS version itself: a later ECS release could move `perCs` to a newer PER.
That would arrive as auto-fixable findings on an ECS update, visible in the consumer's diff, rather than silently.

*Rejected*: listing the 62 rules and 15 configured options explicitly in the template — an exact pin, but some eighty hand-maintained lines that stop tracking ECS's own corrections.
*Rejected*: holding ECS below 13.3 — it keeps the template unchanged by freezing every consumer on an ageing ECS and the php-cs-fixer bundled with it.

### Spacing is stated in the shared set, since `withEditorConfig()` no longer reads anything

This supersedes the [2026-08-28 entry](#witheditorconfig-is-adopted-and-it-cost-the-engine-a-release): ECS 13.3.0 made `withEditorConfig()` a no-op that only prints a deprecation warning, so a consumer's `.editorconfig` no longer reaches ECS at all.
Left alone, ECS falls back to its own default, `withSpacing(spaces, \PHP_EOL)` — CRLF on a Windows machine where CI enforces LF.

The shared set now calls `withSpacing(indentation: Option::INDENTATION_SPACES, lineEnding: "\n")`, which is exactly what the synced `.editorconfig` declares for PHP (`indent_style = space`, `end_of_line = lf`).
A repository indenting with tabs adds `->withSpacing(indentation: Option::INDENTATION_TAB)` to its own `ecs.php`.
*Verified* with ECS 13.3.2: a consumer config importing a set that declares spaces, and declaring tabs itself, keeps a tab-indented file's tabs — the consumer's own call wins, because ECS applies it after importing the sets.

So one shared set still serves a spaces repository and a tabs repository, as the 2026-08-28 entry wanted; the tabs repository now says so in its ECS config rather than only in its `.editorconfig`.

*Rejected*: dropping the call without replacing it — the line ending would follow the machine.
*Rejected*: keeping it until ECS removes it — a warning on every run, and the same fatal break `perCS30:` just caused, only later.

### Correction, the same day: PER-CS has to load after the prepared sets

The first subsection above claimed that the new set left the style where it was, and 0.2.1 shipped on that claim; it was wrong.
Taking 0.2.1 into the engine repository showed 106 findings on its code: imports sorted alphabetically (`OrderedImportsFixer`, 45), `new class extends` gaining parentheses (`NewWithParenthesesFixer`, 42), `new class (` losing its space (`ClassDefinitionFixer`, 7), and empty bodies relaid (`BracesPositionFixer` and `SingleLineEmptyBodyFixer`, 6 each).

*What the verification missed.* It compared ECS's `per-cs.php` with `@PER-CS3.0` in isolation — both true, both identical — but not the configuration in effect once every set has loaded.
In ECS 13.3 `perCs` is one of the prepared sets, and a set loaded after it that registers the same fixer without options resets that fixer to its defaults: bisected, `controlStructures` resets `ClassDefinitionFixer` and `NewWithParenthesesFixer`, and `namespaces` resets `OrderedImportsFixer`.
ECS 13.2 applied `withPhpCsFixerSets(perCS30: true)` as a dynamic set after all prepared sets, so PER-CS's options always won there; this repository's own code satisfied both orderings, which is why its check stayed clean.

*The fix.* The shared set loads PER-CS on its own, after the prepared sets, with `->withSets([SetList::PER_CS])` in place of `perCs: true`.
Verified with ECS 13.3.2: zero findings on the engine's code and on this repository's, which is the behaviour 0.2.0 had.

*The lesson, for the next tool upgrade.* An equivalence check on a set's definition says nothing about precedence; the check that counts is the upgraded tool run over a real consumer's code, which is what caught this.

## Agreed 2026-09-29 — adopting on the 0.3 engine

### Two standards declaring one script now add to it

This resolves the [2026-08-29 collision](#the-first-collision-between-two-standards-and-it-is-silent): from engine 0.3 the declarations of one composer script merge in declaration order, each adding its commands after the earlier ones', instead of the later one replacing the earlier.
A framework standard declared beside a tier therefore adds a step to that tier's `app-checks` by declaring the same script with only that step, without restating the tier's list and whichever tier it is.
*Verified* against the unreleased engine before 0.3.0, with a consumer declaring `ProjectStandard`, a stand-in framework rule set and `ProjectBitbucketStandard`: the step joins `app-checks` after the tier's commands when the framework is declared after the tier and before them when declared before, the engine's lock records the merged list, and the next check is in sync.
That unblocks `SymfonyStandard`, which the [roadmap](roadmap.md) carried as waiting on exactly this.

### Adoption keeps a consumer's own script commands, and the standard documents the cleanup

From engine 0.3 a `ComposerScript` declares commands a script runs rather than owning the whole script: every declared command is enforced present, and every other command in the script is the consumer's and stays.
A repository adopting the standard over scripts it already has under the same names therefore keeps its old command after the declared one — `["phpstan analyse", "php vendor/bin/phpstan --memory-limit=256M"]` runs the analyser twice until the repository deletes its line.

*Measured* on the application tier's first consumer, an existing Symfony application on Bitbucket, with its current `scripts` section synced in a scratch copy: **ten of its ten tool scripts** keep a command of their own beside the declared one — `app-phpstan`, `app-run-tests`, `app-ec` and `app-ec-fix`, `app-csfixer` and `app-csfixer-fix`, `app-phpcs` and `app-phpcs-fix`, `app-rector` and `app-rector-fix`.
Until they are deleted, CI runs each tool twice, and the old runs use the repository's previous flags and configs, so they can fail for reasons the standard no longer has.

**The standard documents the cleanup rather than working around it**: the README's usage section tells an adopter to review the synced `scripts` and delete their own line wherever it runs the same tool.
The engine's behaviour is deliberate — it never removes what a repository wrote and retracts only what it recorded declaring — and its drift report already names every line it kept.

*Rejected*: `acceptsArguments` on the tool scripts to absorb the old commands — they are spelled differently (`php vendor/bin/phpstan …` is not `phpstan analyse` followed by arguments), so tolerance would not match them, and it would open the scripts to weakening flags besides.
*Rejected*: leaving it to the drift report alone — it names the kept lines one script at a time, which does not tell an adopter up front that every tool script needs the same look.

### `app-outdated` stays out of what the standard aggregates, not out of every `app-checks`

The [2026-08-29 entry](#app-outdated-is-declared-and-deliberately-not-aggregated) keeps `app-outdated` out of `app-checks` because it fails on other people's release cadence.
On the 0.3 engine that decision governs what the standard itself declares into the aggregate, and nothing more: a repository whose own `app-checks` already runs `@app-outdated` keeps it, because a step a repository added is its own.
The first consumer is exactly that case — its `app-checks` opens with `@app-outdated`, and after adoption it reads `["@app-sync-check", "@app-outdated", "@app-ec", …, "@app-run-tests"]`.

That is accepted: a repository that deliberately fails CI on outdated dependencies has made its own call, and removing the step would change its pipeline without asking.
The README says "the standard keeps it out of `app-checks`" rather than that it stays outside, because only the first is true for every consumer.

*Rejected*: enforcing the step's absence — it needs an engine rule that does not exist yet (an entry that must be absent, deferred on the engine's roadmap) and would overrule a repository's deliberate choice.
*Rejected*: a strict `app-checks` — it would remove every step a repository added, its own analysers included, which is the behaviour the 0.3 engine moved away from.

## Agreed 2026-09-30 — the package tier's export-ignore block

### What composer installed was the whole repository

Composer installs the zip `git archive` builds, which leaves out every path `.gitattributes` marks `export-ignore`, and the package tier synced no `.gitattributes`.
*Measured* in the installed copies: this package, as the engine installs it, carried its entire repository, `tests/`, `docs/`, its tool configs, sync config and lock included; the engine, as this package installs it, left out the `tests/` and `docs/` its own `.gitattributes` names and still carried `ecs.php`, `phpstan.neon`, `psalm.xml`, `rector.php`, `renovate.json5`, `standards-sync.php` and `standards-sync.lock`.
Nothing reads those files in `vendor/`, so nothing broke; it is weight and noise in every install.

### The block lists every name, not the one a library uses

`.gitattributes` is line-oriented with `#` comments, so it is a managed block like `.gitignore` and needed no engine work.
It lists each tool config under every filename the engine accepts for it, the renovate config's included, because a listed path that does not exist does nothing — verified with `git archive` on git 2.53 — so listing them all costs lines and nothing else.
`.github/` is listed whole, which covers the workflows and `.github/renovate.json` alike.
The package's suite pins the list: every file a rule of the standard can write, under every name, must be covered, with `composer.json` the one exception, since composer installs by it.
A family added later therefore fails the suite until its config is in the block.

*Paths anchor, as in `.gitignore`.* `/CLAUDE.md` covers the root file only, so a `CLAUDE.md` nested deeper still ships; that is rare, and an unanchored pattern is the risk the [authoring conventions](authoring.md#ordering-inside-a-distributed-list-file) warn against.

### Beyond synced files: `tests/` and Claude Code's project files, but not `docs/`

The block also carries `/tests`, because the seeded PHPUnit config names that directory as the suite, so the standard is what puts it there.
It carries `/.claude`, `/CLAUDE.md` and `/.mcp.json` as well: they configure an agent working in the repository and do nothing in an installed package.
*Rejected*: leaving `tests/` to each library, as the roadmap first recorded it — every library the standard serves would write the same line.
*Rejected*: `docs/` in the block — nothing the standard ships names it, so whether a library's documentation travels with its installs stays the library's call, made with a line of its own outside the block.

### An override goes below the block

For one path git applies the later line, verified: `/phpstan.neon -export-ignore` below the block ships the file, and the same line above it loses.
A library that wants a listed file in its installs writes that line below the block.
A first sync appends the block to an existing `.gitattributes`, so an override a repository already had ends up above the block and stops working until it is moved below.

### The application tier ships none of it

An application is not installed as a dependency, so no archive of it ever reaches a `vendor/` directory.

## Agreed 2026-09-30 — renovate over the shipped templates

### This repository's renovate config reads `templates/`

The Renovate app runs on the organisation since 2026-09-30.
Its GitHub Actions manager reads only `.github/workflows/`, `.github/actions/`, `workflow-templates/` and `action.yml` by default, so the templates the shipped workflows are rendered from were invisible to it, and an update to a synced copy alone fails `sync --check`.
This repository's own `renovate.json5` adds `/^templates/.+\.ya?ml$/` to that manager, so one pull request updates a template and its synced copy together.
An extract-only dry run confirmed it: the manager found the three GitHub templates and every action in them, and nothing in the Bitbucket one.
The pattern is anchored to the root, so a nested `templates/` directory, such as a test fixture's, never matches.
*Rejected*: the pattern in the shared preset — every consumer would read its own `templates/`, which in a Symfony application holds Twig, and a consumer receives these updates through a release and a sync, never from the bot.

### The Bitbucket template's image is left to the application tier's floor

The Bitbucket Pipelines manager reads every `*-pipelines.yml` by default, so it found `templates/project/ci-pipelines.yml` unaided and offered `php:8.2-cli` → `php:8.5-cli` on its first run.
That image is the application tier's PHP floor: `ProjectStandard` requires `php: ^8.2`, and the GitHub companion's template pins `php-version: '8.2'`, a `setup-php` input the bot does not read.
Taking the update would have moved only Bitbucket applications' CI off the floor, and the two companions apart.
This repository's `renovate.json5` turns the bot off for that one dependency, so the image moves only with the floor, in the same release as the requirement and the GitHub template.
A lookup dry run confirmed it: the image went from a pending minor update to disabled, and every other dependency's result stayed the same.
*Rejected*: declining the update each time — it holds only while the bot asks for approval before opening anything, and otherwise it is a pull request for every PHP minor.
*Rejected*: `allowedVersions` below the next minor — the same effect today, with the floor written in a fourth place that has to move with the other three.

## Agreed 2026-09-30 — the shipped workflows' actions and runner

### Actions move to their Node 24 majors

The CI run of 2026-09-30 warned that `actions/checkout@v4`, and the `actions/cache` v4.2.4 that `ramsey/composer-install` 3.x pins inside itself, still target Node.js 20, which GitHub has deprecated and already forces onto Node 24.
Every GitHub workflow the standard ships now uses `actions/checkout@v7` and `ramsey/composer-install@v4`; `shivammathur/setup-php@v2` already runs on Node 24 and stays.
`checkout` moved to Node 24 in v5, v6 persists its credentials to a file of their own, and v7 refuses to check out a fork's pull request under `pull_request_target` and `workflow_run` — none of which the shipped workflows touch, and the release job authenticates `gh` with the job token rather than git's credentials.
`composer-install` 4.0.0 changes one thing, its cache to `actions/cache` v5 on Node 24; the major bump is for self-hosted runners, which need runner 2.327.1, and the shipped workflows name a GitHub-hosted label.
*Rejected*: `checkout@v5`, the smallest step off Node 20 — it leaves the standard two majors behind on the day it moves, for no change the workflows would notice.

### The runner label is pinned, to `ubuntu-26.04`

`ubuntu-latest` moves to Ubuntu 26.04 between 2026-10-19 and 2026-11-19, and the shipped workflows now name `ubuntu-26.04` rather than float on the label.
Renovate updates a pinned runner label to the newest one GitHub marks stable and leaves `ubuntu-latest` alone, so with the bot running a pin costs one pull request per Ubuntu LTS and makes each move a release of this standard, tried in its own CI before any consumer syncs it.
The jobs need nothing from the image beyond what `setup-php` installs, and `setup-php` supports 26.04, where PHP 8.5 is preinstalled.
*Rejected*: keeping `ubuntu-latest` — the right call while no bot ran, since a pin nobody updates ages silently until the image is retired; the bot removed that cost.
*Rejected*: pinning `ubuntu-24.04` — it only postpones the same move to that image's retirement.

⚠ The application tier's PHP 8.2 on 26.04 is untried: no CI this standard owns runs the project tier's GitHub companion, so the first application declaring it is the first run.

## Agreed 2026-09-30 — the package tier requires PHPUnit 13

### The floor moves to `^13`, now that Psalm installs beside it

This supersedes the floor in the [2026-08-28 entry](#phpunit-thirteen-strictness-flags-defaults-included); the thirteen flags are unchanged.
`^12` was chosen while Psalm 6.14 to 6.16, the first lines to run on PHP 8.5, could not install beside PHPUnit 13.2 and later over `sebastian/diff`; Psalm 6.17.0 accepts the major those need.
Measured in this repository and the engine on PHPUnit 13.3.6 beside Psalm 6.19.1: every check passes, and `phpunit --validate-configuration` accepts both configs, so all thirteen flags exist in 13. PHPUnit 13 needs PHP 8.4.1, below the tier's `^8.5`.
With the move, every tool floor the tier declares sits on its current major.
*Rejected*: keeping `^12` — as a floor it holds nobody back from 13, but with its reason gone it only leaves each library on the older major until it moves by itself.

## Agreed 2026-09-30 — what the package preset asks of Renovate

### A week's wait, config migration, and PHP left to the standard

The preset now holds every update until its release is seven days old, opens a pull request when Renovate renames one of its own options, and never proposes a change to the `php` requirement.
The wait is the supply-chain guard Renovate's upgrade guidance recommends, and it lets first-day bugs surface; with Renovate's default `internalChecksFilter`, no pull request opens before it has passed.
A lookup dry run confirmed it: with PHPUnit 13.3.6 and Psalm 6.19.1 a day old, the preset proposed 13.3.4 and 6.18.0, the newest releases older than a week.
Pin and digest updates change no version and skip the wait, and so do Docker images, since some publish no timestamp and Renovate's default `minimumReleaseAgeBehaviour` would then hold them back for good.
In a library the wait applies to majors only, the one kind of Composer update the bot proposes without a lock; a release inside the constraints reaches the next CI run unwaited.
`php` is the PHP floor, which the standard declares and moves; left to the bot, PHP 9 would arrive as `^8.5` → `^9.0` in every library, and that passes `sync --check`, since the requirement is a floor.
*Rejected*: `config:best-practices` wholesale — its dev-dependency pinning matches npm's dependency types and not Composer's `require-dev`, its release-age preset is npm-only, and its lock file maintenance has no lock to maintain; what applies to a library is taken here piece by piece, and its GitHub Actions digest pinning waits on how a consumer's managed workflow blocks take updates ([roadmap](roadmap.md)).
*Rejected*: holding major updates for approval on the dashboard — in a library they are the only updates the bot proposes, so it would hold everything.

### Libraries still commit no lock

Re-examined against Renovate: without a lock, an analyser's minor release with new findings reaches a library's CI on its next push rather than as a pull request, and the week's wait never applies to it; a committed lock would turn those into pull requests.
The package tier keeps ignoring `/composer.lock` all the same.
Composer's own documentation notes that a library's lock has no effect on the projects that install it, and of twelve widely used PHP repositories only PHPUnit and `phpstan-src` commit one, both shipped as PHARs; the libraries installed as code — Symfony Console, Doctrine ORM, Laravel, Guzzle, Flysystem, `composer/semver`, PHP-Parser — and the sources of Rector and ECS commit none.

## Corrected 2026-09-30 — the ECS floor is 13.3.2

The [same-day correction of 2026-09-29](#correction-the-same-day-per-cs-has-to-load-after-the-prepared-sets) moved the shared set to `->withSets([SetList::PER_CS])` and left the floor at `^13.3`, but that constant first exists in ECS 13.3.2: checked against 13.3.0, 13.3.1 and 13.3.2, and on either of the first two `ecs check` stops with an undefined constant.
The floor is now `^13.3.2`.
Nothing had caught it because a library without a lock always installs the newest ECS inside the constraint; installing this package at its lowest allowed versions did.
