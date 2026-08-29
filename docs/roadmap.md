# Roadmap

What is ahead for the standard, each item with the trigger that reopens it.
Why what exists is shaped the way it is belongs in the [decision record](decisions.md); this file holds only what has not been settled.

## Open now

- **`armin/editorconfig-cli`, the family that makes the synced `.editorconfig` enforced rather than advisory.**
  Researched 2026-08-29 and **deferred on priority, not on doubt**: the design below is settled and measured, and the work is a normal family tranche whenever it is picked up.

  **The application tier ships it since 2026-08-29**, so what is left here is the package tier and the `.editorconfig` template change the numbers below argue for.
  One finding from that tranche belongs to both: the family needs **no finder-config file**, because `ec -e vendor -e node_modules -e var` matches a directory name at any depth and reproduces a Symfony Finder config byte for byte. Bare `ec` is not equivalent — it reports issues under a committed compiled-asset tree, since `ec` excludes only what `.gitignore` excludes.

  *Measured against the first consumer's 449 tracked files, with `ec` 2.2.1.*

  | Configuration | Issues / files |
  |---|---|
  | The shipped template as-is | 118 / 46 |
  | + `[{*.neon,*.neon.dist}] indent_style = tab` | 54 / 11 |
  | `indent_style` dropped entirely | 6 / 6 |
  | `indent_style` kept, exceptions declared after the block | 6 / 6 |

  112 of the first row are "expected space, found tabs", and once neon is declared tab-indented the remainder is entirely the *generated* rule catalog, which embeds tab-indented neon inside code fences.
  The 2026-08-28 numbers (272 in 53) predate ECS and Rector running over that consumer.

  *The framing that was wrong.* This item used to read "keeping `indent_style` means `ec` can never run", which assumed the standard's block is the whole file.
  It is not: `indent_style = unset` is honoured, and sections a consumer writes after the managed block override it.
  So a repository keeps the standard's hint and neutralises its own exceptional paths in its own part of the file — the co-manage-a-region model the engine is built on — and lands on the same six issues as amputating the setting.
  Keeping `indent_style` and adopting `ec` are therefore not alternatives.

  *The shape agreed 2026-08-29.* The template declares `[{*.neon,*.neon.dist}] indent_style = tab`, since neon's own convention is tabs and the standard already ships PHPStan; the family adds `ComposerRequirement('armin/editorconfig-cli')`, an `app-ec` and an `app-ec-fix` script mirroring the ECS and Rector pairs, and `app-ec` into `app-checks`; the standard applies it to itself, and a consumer's own exceptions live outside the block.

  *`--strict` is rejected, measured.* Without it only the indent *character* is checked, so any amount of spaces passes; with it, leading whitespace must be a multiple of `indent_size`.
  On that consumer with the agreed configuration that is **904 issues in 105 files** — 785 in PHP, from nowdoc fixture bodies and the continuation lines ECS itself produces, plus 68 in markdown where nested bullets indent by two under a global size of four.
  Its fixer also floors rather than rounds, truncating a six-space indent to four, so `--fix --strict` silently reindents fixture strings.
  The honest consequence: `indent_size` stays documentation for editors, and only the indent character is enforced.

  *Two facts about `ec` worth keeping.* `[*.md] trim_trailing_whitespace = false` exempts *lines* but not the *end of file* — read from `Validator`, the line rule is built only when the value is true, while the file-level rtrim rule is built whenever the property is set at all, whatever its value. So under `ec` no file may end in a blank line, markdown included.
  And `--skip` cannot rescue a rule: `ec`'s skip map inverts two of its own entries, so the valid option name is rewritten into an invalid one and rejected (2.0.1 through 2.2.1).

  *Adoption cost for the first consumer, applied and inspected*: six auto-fixed changes — one trailing blank line removed from each of five history documents, and a final newline added to `docker-compose.yml`.

- **The application tier's ratchets.**
  `ProjectStandard` ships declared at what its first consumer can pass today; each gap below is a tranche, and each is blocked on that consumer's dependency upgrade rather than on any decision.
  The upgrade is the trigger for all four, and it is needed for its own sake: that application's installed toolchain does not run on current PHP at all — `php-cs-fixer` 3.65 refuses outright above 8.3, and Psalm crashes loading its autoloader because a transitive dependency still uses `case X;`.

  | Ratchet | From | To | Measured cost |
  |---|---|---|---|
  | PHP floor | `^8.2` | `^8.5` | the upgrade itself; the two CI templates move with it |
  | PHPUnit | `^9.6`, 8 flags | `^12`, all 13 | unmeasured — it cannot be measured from outside the repository |
  | Rector's PHP set | `PHP_82` | `PHP_85` | nil today: the `PHP_85` set fired on nothing |

- **Psalm in the application tier.**
  Not shipped, because the first consumer does not use it. The cost of adopting it is measured and small at the loose end: **31 findings at errorLevel 6 or 8, every one of them `MissingOverrideAttribute`**, which `psalm --alter --issues=MissingOverrideAttribute` fixes in one sweep — so errorLevel 6 is one command from clean.
  errorLevel 4 costs 9 more, errorLevel 2 costs 116 more (`ClassMustBeFinal` 30, `MissingConstructor` 20, `MissingClassConstType` 17, `PropertyNotSetInConstructor` 15).
  `findUnusedCode` stays **off**, measured — see the decision record; turning it on needs a framework-aware plugin, which belongs to a framework standard.
  Trigger: an application willing to run the sweep.

- **ECS in the application tier, and the two rule families it waits on.**
  The tier requires `php-cs-fixer` and `phpcs` and gives them entry points, but ships **no shared rule set for either** — nothing in the engine can carry one, and they are two new rule families.
  Moving both through ECS instead is the preferred direction and costs **50 files, every finding auto-fixable**, measured with the package tier's own set. What it does not do is remove the tools it replaces: there is no enforce-absence rule, so `.php-cs-fixer.dist.php` and `phpcs.xml.dist` stay behind for a consumer to delete by hand.
  Trigger: either the two rule families, or a consumer willing to migrate to ECS.

- **`SymfonyStandard`, additive and tier-neutral.**
  A framework standard adds rule *sets* (Rector's Symfony and Twig sets, a framework-aware Psalm plugin) rather than replacing files, so it composes beside either tier without a cross-product.
  Framework path lists and bootstrap-file exclusions are deliberately *not* part of it: measured under the target tool versions, those five files produce only trivial auto-fixable findings, so the exclusions the archetype carries are artefacts of its old configs rather than necessities. The one real reason to exclude two of them is that Symfony Flex rewrites them.
  **Blocked on an engine gap**, not on design: a framework standard needs to add a step to `app-checks`, and `ComposerScript` replaces a script's command list rather than merging it, so the later rule set silently wins. See the decision record; the engine's roadmap needs the item.

- **The extraction of `OrthoCodeStandard`.**
  Both tiers exist now, so the overlap is visible and the base can be extracted from them rather than designed ahead of them.
  What is genuinely shared today: the `.editorconfig` block, `treatPhpDocTypesAsCertain: false`, `sort-packages`, `roave/security-advisories`, the sync scripts, and the shape of every `app-*` entry point.
  What is not, and should stay per tier: the lock file in `.gitignore`, the PHP floor, the PHPStan level, the analyser set, and CI.
  Read the template-move item at the bottom of this file before starting — it constrains how the extraction may move files.

## Deferred, with recorded triggers

- **Deptrac is agreed in shape and not shipped.**
  The decision, taken while the tiers were being designed, was a tool requirement and a check-script entry with **no depfile template**: a layerless depfile analyses clean — exit 0, zero violations, zero uncovered, and still exit 0 under `--fail-on-uncovered` — so requiring deptrac org-wide imposes no shared layer model, and a consumer with no depfile fails loudly until it declares its own layers.
  No phase ever landed it, which is why it appears here rather than in the decision record.
  Trigger: none needed — it is a family to pick up, and the upgrade path if shared layers ever appear is the engine's import rule pointing at a shared template.

- **The CI block owns the whole file, and the runtime image is where that bites.**
  Both forge companions ship CI as a managed block, and a single-document YAML block effectively owns its file — so the one thing an application is most likely to need to change is the one thing it cannot: the runtime image and the extensions built into it. The shipped Bitbucket pipeline runs a bare PHP image, which is enough to run the checks and not enough for an application needing `ext-intl` or its like, and such a repository's only route today is disabling the rule wholesale.
  This is the **second** instance of the same engine gap the reusable-workflow item below records, now in a place where the variation is a hard requirement rather than a convenience.
  Directions are the engine's to choose (named insertion points, per-target composition, or shipping a callable workflow consumers wrap); until one lands, an application whose CI needs more than the block gives writes its own pipeline and does not declare the forge companion.
  Trigger: the first application whose runtime the shipped image cannot provide.

- **Renovate, four things left open.**

  *Preset indirection versus syncing the rules directly.* Writing the rules into each consumer's own renovate config rather than pointing at a shared preset is the preferred direction; both work, and they differ in when a change propagates. A preset changes for every consumer the moment the bot next runs, with no sync and no pull request; synced rules need a sync per repository but need no forge fetch and are readable in the repository. Doing it needs a new engine rule family — nothing today writes arbitrary renovate settings, only the `extends` entry.

  *Bot plumbing.* A preset and an `extends` entry configure nothing until renovate actually runs: that means installing the Renovate app on the organisation, or self-hosting it on a schedule in a workflow. Organisation setup rather than repository config, and outside anything file sync can reach.

  *Grouping `require-dev` upgrades into one pull request* — less noise, at the cost of coupling unrelated upgrades.

  *Automerging dev-dependency patch and minor upgrades* — this was blocked until the standard shipped CI, since automerge without checks merges blind. It ships CI now, so the block is gone and only the decision is left.

- **A generic seed-if-absent rule, for changelogs and their like.**
  The release family shipped the workflow as a managed block and left `CHANGELOG.md` to each repository.
  Seeding one has no rule today: the engine's one-shot seeds are all tool-specific, and a managed block is wrong for a file of human prose that must not carry markers.
  Trigger: a second repository where starting the changelog by hand is friction rather than a one-line chore.

- **Ship the CI workflow as a *reusable* workflow.**
  Today `standards.yml` is a block running `app-checks` and nothing else, and both the block and the script are standard-owned, so a repository wanting one extra step in that job has no route: it adds a second workflow file beside it, or disables the rule wholesale.
  A reusable workflow — the standard ships the callable one, each repository's thin workflow calls it and adds its steps — gives the variation without touching the engine.
  The general form of the problem is an engine concern and recorded there: a managed block has no extension point, which only goes unnoticed in line-oriented files where concatenation is composition.
  Trigger: the second repository that wants a CI step the standard does not ship.

- **Moving a template is no longer free.**
  Templates live under `templates/package/` so that shared files can take `templates/shared/` at extraction time.
  While only managed blocks carried them the content was copied and the consumer never saw the path, but the ECS set, the Rector set and the PHPStan ruleset all render their path into the consumer's own config, and sync adds without retracting.
  So a move now leaves a stale entry beside the new one in every consumer, exactly as the preset rename did.
  This has to be settled inside the extraction work rather than after it: either shared templates keep the paths consumers already hold, or the move is priced as a migration across every consumer.
