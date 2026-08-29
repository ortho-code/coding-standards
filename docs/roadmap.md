# Roadmap

What is ahead for the standard, each item with the trigger that reopens it.
Why what exists is shaped the way it is belongs in the [decision record](decisions.md); this file holds only what has not been settled.

## Open now

- **The `.editorconfig` indentation decision, and with it `armin/editorconfig-cli`.**
  `ec` is the only tool that would make the synced `.editorconfig` enforced rather than advisory, which is the whole argument for distributing it, so it was deferred rather than dropped.
  The decision is narrow: whether the shared `.editorconfig` keeps `indent_style`.

  *Why it is not free.* Measured against the first consumer's 442 tracked files with the shipped template: **272 issues in 53 files**, of which **266** were "expected space, found tabs".
  Adding `[*.neon] indent_style = tab` brought it to **224 in 23**.
  The remainder is unfixable by any `.editorconfig` section — those tabs live inside PHP nowdoc fixtures and inside generated markdown that embeds tab-indented neon verbatim, and `ec` reads lines with no notion of string literals or code fences.
  Dropping `indent_style` from the template brought it to **6 issues in 6 files**, all auto-fixable, because the validator builds its indentation rule only when a style is declared — `indent_size` alone is inert.
  `--skip` cannot rescue it: `ec`'s skip map inverts two of its own entries, so the valid option name is rewritten into an invalid one and rejected (2.0.1 through 2.2.1).

  *What each side buys.* Keeping `indent_style` means `ec` can never run; dropping it buys enforced charset, line endings, final newline and trailing whitespace at the cost of the editor's space-versus-tab hint, with `indent_size` retainable either way.
  ECS enforces PHP indentation regardless, which removes most of what the hint was for, and a fleet that ever has to serve a tabs-for-PHP convention would want `indent_style` out of a shared file anyway.

  *Before deciding, re-measure.* Those numbers predate ECS and Rector running over that consumer, so the trailing-whitespace and final-newline tail has probably already closed; the nowdoc tabs will not have moved, since fixers work on tokens and never touch a nowdoc body.

  Also decide, if `ec` is adopted, whether the toolchain runs it under `--strict`: indent *size* is checked only there, and even then only as a multiple of the declared size, so a four-space file passes where two is declared. Indent *style* is always checked.

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
