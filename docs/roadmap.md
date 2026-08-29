# Roadmap

What is ahead for the standard, each item with the trigger that reopens it.
Why what exists is shaped the way it is belongs in the [decision record](decisions.md); this file holds only what has not been settled.

## Open now

- **`armin/editorconfig-cli`, the family that makes the synced `.editorconfig` enforced rather than advisory.**
  Researched 2026-08-29 and **deferred on priority, not on doubt**: the design below is settled and measured, and the work is a normal family tranche whenever it is picked up.

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

- **`ProjectStandard`, then the extraction of `OrthoCodeStandard`.**
  The package tier is real, so the second tier is now buildable, and the shared base is extracted from the two rather than designed ahead of them.
  Known differences to carry: the project tier commits `composer.lock`, wants Psalm's `findUnusedCode` on, and may take a different view of Rector's `PRIVATIZATION` set — all three are recorded in the decision record as library-specific choices rather than general ones.
  An existing application is the archetype to port: it uses php-cs-fixer plus PHPCS sniffs rather than ECS, and the tier should move those rule sets through ECS, which runs both — without that, a project tier would need two new engine rule families.
  Two items below unblock as soon as this lands.

## Deferred, with recorded triggers

- **Deptrac is agreed in shape and not shipped.**
  The decision, taken while the tiers were being designed, was a tool requirement and a check-script entry with **no depfile template**: a layerless depfile analyses clean — exit 0, zero violations, zero uncovered, and still exit 0 under `--fail-on-uncovered` — so requiring deptrac org-wide imposes no shared layer model, and a consumer with no depfile fails loudly until it declares its own layers.
  No phase ever landed it, which is why it appears here rather than in the decision record.
  Trigger: none needed — it is a family to pick up, and the upgrade path if shared layers ever appear is the engine's import rule pointing at a shared template.

- **`roave/security-advisories` cannot be required, so it was dropped.**
  The engine's `ComposerRequirement` refuses a branch constraint by design — it states no minimum version to enforce — and that package publishes only `dev-master` and `dev-latest`.
  Trigger: an engine rule that enforces a requirement's *presence* rather than a version floor. Roave is the first real case for one.

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
