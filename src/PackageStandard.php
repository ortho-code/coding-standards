<?php

declare(strict_types=1);

namespace OrthoCode\CodingStandards;

use OrthoCode\StandardsSync\Authoring\Package;
use OrthoCode\StandardsSync\Authoring\Standard;
use OrthoCode\StandardsSync\Core\Rule\FileTarget;
use OrthoCode\StandardsSync\Rules\Composer\ConfigSetting\ComposerConfigSetting;
use OrthoCode\StandardsSync\Rules\Composer\Requirement\ComposerRequirement;
use OrthoCode\StandardsSync\Rules\Composer\Requirement\RequirementType;
use OrthoCode\StandardsSync\Rules\Composer\Requirement\VersionConstraint;
use OrthoCode\StandardsSync\Rules\Composer\Script\ComposerScript;
use OrthoCode\StandardsSync\Rules\Ecs\BaseSet\EcsBaseSet;
use OrthoCode\StandardsSync\Rules\General\ManagedBlock\Label;
use OrthoCode\StandardsSync\Rules\General\ManagedBlock\ManagedBlock;
use OrthoCode\StandardsSync\Rules\PhpStan\IncludedRuleset\PhpStanIncludedRuleset;
use OrthoCode\StandardsSync\Rules\PhpStan\MinLevel\PhpStanLevel;
use OrthoCode\StandardsSync\Rules\PhpStan\MinLevel\PhpStanMinLevel;
use OrthoCode\StandardsSync\Rules\PhpStan\PinnedValues\PhpStanPinnedValues;
use OrthoCode\StandardsSync\Rules\PhpStan\PinnedValues\PinnedValues;
use OrthoCode\StandardsSync\Rules\PhpUnit\BaseConfig\PhpUnitBaseConfig;
use OrthoCode\StandardsSync\Rules\PhpUnit\PinnedAttributes\PhpUnitPinnedAttributes;
use OrthoCode\StandardsSync\Rules\Psalm\BaseConfig\PsalmBaseConfig;
use OrthoCode\StandardsSync\Rules\Psalm\LoosestErrorLevel\PsalmErrorLevel;
use OrthoCode\StandardsSync\Rules\Psalm\LoosestErrorLevel\PsalmLoosestErrorLevel;
use OrthoCode\StandardsSync\Rules\Rector\BaseSet\RectorBaseSet;
use OrthoCode\StandardsSync\Rules\Renovate\ExtendedPreset\RenovateExtendedPreset;
use OrthoCode\StandardsSync\Rules\Renovate\RenovateConfigFormat;
use Override;

/** OrthoCode's standard for library packages; applications get their own tier. */
final class PackageStandard extends Standard
{
    private const string LABEL = 'ortho-code';

    #[Override]
    protected function enforce(Package $package): void
    {
        $this->enforceEditorConfig($package);
        $this->enforceGitignore($package);
        $this->enforcePhpUnit($package);
        $this->enforceEcs($package);
        $this->enforceRector($package);
        $this->enforcePhpStan($package);
        $this->enforcePsalm($package);
        $this->enforceRenovate();
        $this->enforceRelease($package);
        $this->enforceToolchain($package);
    }

    private function enforceEditorConfig(Package $package): void
    {
        $this->addRule(new ManagedBlock(
            target: FileTarget::fromString('.editorconfig'),
            label: Label::fromString(self::LABEL),
            content: $package->read('package/.editorconfig'),
        ));
    }

    private function enforceGitignore(Package $package): void
    {
        $this->addRule(new ManagedBlock(
            target: FileTarget::fromString('.gitignore'),
            label: Label::fromString(self::LABEL),
            content: $package->read('package/.gitignore'),
        ));
    }

    /** A seeded phpunit.xml, then every strictness flag pinned — including the two phpunit already defaults to true, so the config states the whole contract instead of half of it. */
    private function enforcePhpUnit(Package $package): void
    {
        // The template declares first: phpunit has no import tier, so an absent config grows from it rather than the engine skeleton.
        $this->addRule(new PhpUnitBaseConfig(config: $package->read('package/phpunit.xml')));
        // Every flag here must exist in each phpunit major the requirement below allows; raise the flags and the floor together.
        $this->addRule(new PhpUnitPinnedAttributes(attributes: [
            'beStrictAboutChangesToGlobalState' => true,
            'beStrictAboutOutputDuringTests' => true,
            'beStrictAboutTestsThatDoNotTestAnything' => true,
            'failOnDeprecation' => true,
            'failOnEmptyTestSuite' => true,
            'failOnIncomplete' => true,
            'failOnNotice' => true,
            'failOnPhpunitDeprecation' => true,
            'failOnPhpunitNotice' => true,
            'failOnPhpunitWarning' => true,
            'failOnRisky' => true,
            'failOnSkipped' => true,
            'failOnWarning' => true,
        ]));
        $this->addRule(new ComposerRequirement(package: 'phpunit/phpunit', constraint: VersionConstraint::fromString('^12')));
        $this->addRule(new ComposerScript(name: 'app-run-tests', commands: ['phpunit']));
    }

    /** The shared fixer set, wired as one entry in the consumer's own withSets() — the set itself rides composer update. */
    private function enforceEcs(Package $package): void
    {
        $this->addRule(new EcsBaseSet(set: $package->path('package/ecs.php')));
        $this->addRule(new ComposerRequirement(package: 'symplify/easy-coding-standard', constraint: VersionConstraint::fromString('^13.2')));
        $this->addRule(new ComposerScript(name: 'app-ecs', commands: ['ecs check']));
        $this->addRule(new ComposerScript(name: 'app-ecs-fix', commands: ['ecs check --fix']));
    }

    /** The shared refactoring set, wired as one entry in the consumer's own withSets() — the set itself rides composer update. */
    private function enforceRector(Package $package): void
    {
        $this->addRule(new RectorBaseSet(set: $package->path('package/rector.php')));
        $this->addRule(new ComposerRequirement(package: 'rector/rector', constraint: VersionConstraint::fromString('^2.5')));
        $this->addRule(new ComposerScript(name: 'app-rector', commands: ['rector process --dry-run']));
        $this->addRule(new ComposerScript(name: 'app-rector-fix', commands: ['rector process']));
    }

    /** The shared ruleset via a native import, a level floor, and values no project may override — overridable defaults belong in the ruleset instead. */
    private function enforcePhpStan(Package $package): void
    {
        // The import declares first: the first rule to meet an absent config decides the created base.
        $this->addRule(new PhpStanIncludedRuleset(ruleset: $package->path('package/phpstan.neon')));
        $this->addRule(new PhpStanMinLevel(minLevel: PhpStanLevel::fromInt(6)));
        $this->addRule(new PhpStanPinnedValues(values: PinnedValues::fromArray([
            'parameters' => [
                'treatPhpDocTypesAsCertain' => false,
            ],
        ])));
        $this->addRule(new ComposerRequirement(package: 'phpstan/phpstan', constraint: VersionConstraint::fromString('^2.2')));
        $this->addRule(new ComposerScript(name: 'app-phpstan', commands: ['phpstan analyse']));
    }

    /** A seeded psalm.xml with an error-level limit — numerically a ceiling, since psalm's scale is inverted, semantically the strictness floor. */
    private function enforcePsalm(Package $package): void
    {
        // The template declares first: psalm has no import tier, so an absent config grows from it rather than the engine skeleton.
        // It also turns findUnusedCode off, which psalm defaults to on: a library's public API is uncalled from inside by design.
        $this->addRule(new PsalmBaseConfig(config: $package->read('package/psalm.xml')));
        $this->addRule(new PsalmLoosestErrorLevel(loosest: PsalmErrorLevel::fromInt(2)));
        $this->addRule(new ComposerRequirement(package: 'vimeo/psalm', constraint: VersionConstraint::fromString('^6')));
        $this->addRule(new ComposerScript(name: 'app-psalm', commands: ['psalm']));
    }

    /** The shared preset via a native extends entry. The preset itself lives at this repository's root, not under templates/: the renovate bot fetches it over the forge API, never from a composer install. */
    private function enforceRenovate(): void
    {
        $this->addRule(new RenovateExtendedPreset(
            preset: 'local>ortho-code/coding-standards:renovate-package-preset',
            createAs: RenovateConfigFormat::Json5,
            comment: 'ortho-code coding standards; sync re-adds this entry',
        ));
    }

    /** A tag-driven release whose notes are the CHANGELOG section for that tag. The changelog itself stays each repository's own prose — the standard ships the mechanism, not the content. */
    private function enforceRelease(Package $package): void
    {
        $this->addRule(new ManagedBlock(
            target: FileTarget::fromString('.github/workflows/release.yml'),
            label: Label::fromString(self::LABEL),
            content: $package->read('package/ci-release.yml'),
        ));
    }

    /** Synced configs enforce nothing until something runs them: app-checks is the entry point developers and CI call by name, and every family added later appends to it. */
    private function enforceToolchain(Package $package): void
    {
        // The runtime floor the shipped workflow's php-version assumes; raise the two together.
        $this->addRule(new ComposerRequirement(package: 'php', constraint: VersionConstraint::fromString('^8.5'), type: RequirementType::Runtime));
        // A conflicts-only package with no releases to floor: the branch is pinned, and an explicit dev constraint needs no stability flag of its own.
        $this->addRule(new ComposerRequirement(package: 'roave/security-advisories', constraint: VersionConstraint::fromString('dev-latest')));
        $this->addRule(new ComposerConfigSetting(setting: 'sort-packages', value: true));
        // Composer puts vendor/bin on PATH for scripts, so entries name the bare binary.
        $this->addRule(new ComposerScript(name: 'app-sync', commands: ['standards-sync sync']));
        $this->addRule(new ComposerScript(name: 'app-sync-check', commands: ['standards-sync sync --check']));
        $this->addRule(new ComposerScript(name: 'app-checks', commands: ['@app-sync-check', '@app-ecs', '@app-phpstan', '@app-psalm', '@app-rector', '@app-run-tests']));
        // The workflow calls app-checks by name and nothing in the engine ties the two together, so the suite pins it.
        $this->addRule(new ManagedBlock(
            target: FileTarget::fromString('.github/workflows/standards.yml'),
            label: Label::fromString(self::LABEL),
            content: $package->read('package/ci-standards.yml'),
        ));
    }
}
