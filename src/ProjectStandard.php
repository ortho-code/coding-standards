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
use OrthoCode\StandardsSync\Rules\General\ManagedBlock\Label;
use OrthoCode\StandardsSync\Rules\General\ManagedBlock\ManagedBlock;
use OrthoCode\StandardsSync\Rules\PhpStan\IncludedRuleset\PhpStanIncludedRuleset;
use OrthoCode\StandardsSync\Rules\PhpStan\MinLevel\PhpStanLevel;
use OrthoCode\StandardsSync\Rules\PhpStan\MinLevel\PhpStanMinLevel;
use OrthoCode\StandardsSync\Rules\PhpStan\PinnedValues\PhpStanPinnedValues;
use OrthoCode\StandardsSync\Rules\PhpStan\PinnedValues\PinnedValues;
use OrthoCode\StandardsSync\Rules\PhpUnit\BaseConfig\PhpUnitBaseConfig;
use OrthoCode\StandardsSync\Rules\PhpUnit\PinnedAttributes\PhpUnitPinnedAttributes;
use OrthoCode\StandardsSync\Rules\Rector\BaseSet\RectorBaseSet;
use Override;

/**
 * OrthoCode's standard for applications; library packages get their own tier.
 * Its values are what an application can pass today rather than where the standard is heading — every one of them has a ratchet recorded in docs/roadmap.md.
 * CI belongs to a forge companion (ProjectGitHubStandard, ProjectBitbucketStandard), which a consumer declares beside this one.
 */
final class ProjectStandard extends Standard
{
    private const string LABEL = 'ortho-code';

    #[Override]
    protected function enforce(Package $package): void
    {
        $this->enforceEditorConfig($package);
        $this->enforceGitignore($package);
        $this->enforcePhpUnit($package);
        $this->enforcePhpCsFixer();
        $this->enforceCodeSniffer();
        $this->enforceRector($package);
        $this->enforcePhpStan($package);
        $this->enforceToolchain();
    }

    /** The shared editor settings, plus the tool that makes them enforced rather than advisory. */
    private function enforceEditorConfig(Package $package): void
    {
        $this->addRule(new ManagedBlock(
            target: FileTarget::fromString('.editorconfig'),
            label: Label::fromString(self::LABEL),
            content: $package->read('project/.editorconfig'),
        ));
        $this->addRule(new ComposerRequirement(package: 'armin/editorconfig-cli', constraint: VersionConstraint::fromString('^2.0')));
        // ec excludes what .gitignore excludes, which misses a compiled asset tree committed inside a tracked directory; -e matches a directory name at any depth and needs no config file of its own.
        $this->addRule(new ComposerScript(name: 'app-ec', commands: ['ec -e vendor -e node_modules -e var']));
        $this->addRule(new ComposerScript(name: 'app-ec-fix', commands: ['ec --fix -e vendor -e node_modules -e var']));
    }

    private function enforceGitignore(Package $package): void
    {
        $this->addRule(new ManagedBlock(
            target: FileTarget::fromString('.gitignore'),
            label: Label::fromString(self::LABEL),
            content: $package->read('project/.gitignore'),
        ));
    }

    /** A seeded phpunit.xml, then the strictness flags pinned. */
    private function enforcePhpUnit(Package $package): void
    {
        // The template declares first: phpunit has no import tier, so an absent config grows from it rather than the engine skeleton.
        $this->addRule(new PhpUnitBaseConfig(config: $package->read('project/phpunit.xml')));
        // Eight of the thirteen the package tier pins: the other five reach the phpunit schema after the version floor below, and pinning an attribute the schema lacks makes phpunit's own config validation complain.
        $this->addRule(new PhpUnitPinnedAttributes(attributes: [
            'beStrictAboutChangesToGlobalState' => true,
            'beStrictAboutOutputDuringTests' => true,
            'beStrictAboutTestsThatDoNotTestAnything' => true,
            'failOnEmptyTestSuite' => true,
            'failOnIncomplete' => true,
            'failOnRisky' => true,
            'failOnSkipped' => true,
            'failOnWarning' => true,
        ]));
        $this->addRule(new ComposerRequirement(package: 'phpunit/phpunit', constraint: VersionConstraint::fromString('^9.6')));
        $this->addRule(new ComposerScript(name: 'app-run-tests', commands: ['phpunit']));
    }

    /** The tool and its entry points; the rule set stays each repository's own until a shared one has a rule family to carry it. */
    private function enforcePhpCsFixer(): void
    {
        $this->addRule(new ComposerRequirement(package: 'friendsofphp/php-cs-fixer', constraint: VersionConstraint::fromString('^3.65')));
        $this->addRule(new ComposerScript(name: 'app-csfixer', commands: ['php-cs-fixer fix --dry-run --diff']));
        $this->addRule(new ComposerScript(name: 'app-csfixer-fix', commands: ['php-cs-fixer fix --diff']));
    }

    /** The sniffer and the sniff library it runs; as with the fixer, the ruleset itself is not shared yet. */
    private function enforceCodeSniffer(): void
    {
        $this->addRule(new ComposerRequirement(package: 'squizlabs/php_codesniffer', constraint: VersionConstraint::fromString('^3.11')));
        $this->addRule(new ComposerRequirement(package: 'slevomat/coding-standard', constraint: VersionConstraint::fromString('^8.15')));
        $this->addRule(new ComposerScript(name: 'app-phpcs', commands: ['phpcs -p -s']));
        $this->addRule(new ComposerScript(name: 'app-phpcs-fix', commands: ['phpcbf -p -s']));
    }

    /** The shared refactoring set, wired as one entry in the consumer's own withSets() — the set itself rides composer update. */
    private function enforceRector(Package $package): void
    {
        $this->addRule(new RectorBaseSet(set: $package->path('project/rector.php')));
        $this->addRule(new ComposerRequirement(package: 'rector/rector', constraint: VersionConstraint::fromString('^2.5')));
        $this->addRule(new ComposerScript(name: 'app-rector', commands: ['rector process --dry-run']));
        $this->addRule(new ComposerScript(name: 'app-rector-fix', commands: ['rector process']));
    }

    /** The shared ruleset via a native import, a level floor, and values no project may override. */
    private function enforcePhpStan(Package $package): void
    {
        // The import declares first: the first rule to meet an absent config decides the created base.
        $this->addRule(new PhpStanIncludedRuleset(ruleset: $package->path('project/phpstan.neon')));
        $this->addRule(new PhpStanMinLevel(minLevel: PhpStanLevel::fromInt(9)));
        $this->addRule(new PhpStanPinnedValues(values: PinnedValues::fromArray([
            'parameters' => [
                'treatPhpDocTypesAsCertain' => false,
            ],
        ])));
        $this->addRule(new ComposerRequirement(package: 'phpstan/phpstan', constraint: VersionConstraint::fromString('^2.2')));
        $this->addRule(new ComposerScript(name: 'app-phpstan', commands: ['phpstan analyse']));
    }

    /** Synced configs enforce nothing until something runs them: app-checks is the entry point developers and CI call by name. */
    private function enforceToolchain(): void
    {
        // The runtime floor the shipped CI templates assume; raise the three together.
        $this->addRule(new ComposerRequirement(package: 'php', constraint: VersionConstraint::fromString('^8.2'), type: RequirementType::Runtime));
        // A conflicts-only package with no releases to floor: the branch is pinned, and an explicit dev constraint needs no stability flag of its own.
        $this->addRule(new ComposerRequirement(package: 'roave/security-advisories', constraint: VersionConstraint::fromString('dev-latest')));
        $this->addRule(new ComposerConfigSetting(setting: 'sort-packages', value: true));
        // Composer puts vendor/bin on PATH for scripts, so entries name the bare binary.
        $this->addRule(new ComposerScript(name: 'app-sync', commands: ['standards-sync sync']));
        $this->addRule(new ComposerScript(name: 'app-sync-check', commands: ['standards-sync sync --check']));
        // Declared but deliberately outside app-checks: it fails on other people's release cadence rather than on anything the repository did.
        $this->addRule(new ComposerScript(name: 'app-outdated', commands: ['@composer outdated --strict --no-dev']));
        $this->addRule(new ComposerScript(name: 'app-checks', commands: ['@app-sync-check', '@app-ec', '@app-csfixer', '@app-phpcs', '@app-phpstan', '@app-rector', '@app-run-tests']));
    }
}
