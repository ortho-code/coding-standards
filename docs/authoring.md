# How this standard is authored

Conventions for writing the standards in this package. None of this is enforced by the engine — the engine's own guide ([`authoring-org-packages.md`](https://github.com/ortho-code/standards-sync/blob/main/docs/authoring-org-packages.md)) documents what the engine requires and how it behaves; this file documents what we chose on top of it.

## Where classes live

A standard sits at the package's namespace root: it is the public thing a consumer names, and `extends Standard` already says what kind of class it is, so the name carries the tier instead (`PackageStandard`, `ProjectStandard`). Supporting classes earn folders when they appear, not before.

## Structuring a standard

A standard covering many tools outgrows one readable method. We structure it by tool family: one private method per family, `enforce()` reduced to the list of calls, the enforcement chain last. Each family method holds everything its tool's story needs — the rules, the values inline, the family's docblock, and the ordering comment where declaration order carries meaning:

```php
protected function enforce(Package $package): void
{
    $this->enforceEditorConfig($package);
    $this->enforcePhpStan($package);
    $this->enforceToolchain($package);
}

/** The shared ruleset via a native import, with a level floor. */
private function enforcePhpStan(Package $package): void
{
    // The import declares first: the first rule to meet an absent config decides the created base.
    $this->addRule(new PhpStanIncludedRuleset(ruleset: $package->path('phpstan.neon')));
    $this->addRule(new PhpStanMinLevel(minLevel: PhpStanLevel::fromInt(6)));
}
```

This grouping localizes every ordering constraint the engine's declaration-order rules impose: import-before-values and template-before-pins order rules within one family's method, and tiers order at the `include()` seam. The call order across families is free, since families target different files.

## Values stay inline

A standard optimizes for the maintainer who updates it, not for machinery: plain declarations, values a reader can change in place. A value used once needs no constant — the rule's named argument already names it. A constant earns its place when several declarations share it, like a marker label used by several blocks.

This is the deliberate inverse of the engine's own constants-over-literals convention, which exists because engine code has callers; a standard's values have readers.

## Ordering inside a distributed list file

`.gitignore` and files like it are ordered as a filesystem lists them: directories first, then files, each alphabetically and case-insensitively, with the leading dot counted so hidden entries group ahead of plain ones. The anchoring characters take no part in the sort.

Blank lines separate entries that mean different things: root-anchored directories, then root-anchored files, then the patterns that match at any depth. A leading `/` restricts an entry to the repository root, and an entry without one matches everywhere, so the last group is the one a reader should look at twice.

Anchor by default. An unanchored pattern silently swallows matches in subdirectories, which is a live risk in a repository whose test fixtures are themselves miniature repositories.

## Testing

We test this package's *contribution* — that the right content is wired to the right file — not the engine's rendering, which the engine's own scenario suite pins.

- `SyncTester` for presence checks: sync in memory, assert the block and the content land. This is the fit when the synced file is a whole file a fixture would only duplicate; asserting exact bytes there restates the template and pins nothing we own.
- `ScenarioTestCase` with fixtures when we ship **custom rules** of our own — then each rule's fixtures are its behaviour catalog, exactly as the engine tests its rule library. A standard that only composes shipped rules needs no fixtures.
- Transposed pins where two declarations must agree and nothing ties them together: a template's written value read back through the engine's own writer and asserted against the rule that enforces it, and the CI call asserted against the script name the standard declares.

The last one is the important habit. Wherever the standard says the same thing twice — a template value and its pin, a workflow call and its script name — the engine will not notice them drifting apart, so the package's own suite has to.
