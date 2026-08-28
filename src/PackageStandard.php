<?php

declare(strict_types=1);

namespace OrthoCode\CodingStandards;

use OrthoCode\StandardsSync\Authoring\Package;
use OrthoCode\StandardsSync\Authoring\Standard;
use OrthoCode\StandardsSync\Core\Rule\FileTarget;
use OrthoCode\StandardsSync\Rules\Composer\ConfigSetting\ComposerConfigSetting;
use OrthoCode\StandardsSync\Rules\Composer\Requirement\ComposerRequirement;
use OrthoCode\StandardsSync\Rules\Composer\Requirement\VersionConstraint;
use OrthoCode\StandardsSync\Rules\Composer\Script\ComposerScript;
use OrthoCode\StandardsSync\Rules\General\ManagedBlock\Label;
use OrthoCode\StandardsSync\Rules\General\ManagedBlock\ManagedBlock;
use OrthoCode\StandardsSync\Rules\PhpUnit\BaseConfig\PhpUnitBaseConfig;
use OrthoCode\StandardsSync\Rules\PhpUnit\PinnedAttributes\PhpUnitPinnedAttributes;

/** OrthoCode's standard for library packages; applications get their own tier. */
final class PackageStandard extends Standard
{
    private const string LABEL = 'ortho-code';

    protected function enforce(Package $package): void
    {
        $this->enforceEditorConfig($package);
        $this->enforceGitignore($package);
        $this->enforcePhpUnit($package);
        $this->enforceToolchain();
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

    /** Synced configs enforce nothing until something runs them: app-checks is the entry point developers and CI call by name, and every family added later appends to it. */
    private function enforceToolchain(): void
    {
        $this->addRule(new ComposerConfigSetting(setting: 'sort-packages', value: true));
        // Composer puts vendor/bin on PATH for scripts, so entries name the bare binary.
        $this->addRule(new ComposerScript(name: 'app-sync', commands: ['standards-sync sync']));
        $this->addRule(new ComposerScript(name: 'app-sync-check', commands: ['standards-sync sync --check']));
        $this->addRule(new ComposerScript(name: 'app-checks', commands: ['@app-sync-check', '@app-run-tests']));
    }
}
