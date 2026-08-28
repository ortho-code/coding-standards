<?php

declare(strict_types=1);

namespace OrthoCode\CodingStandards;

use OrthoCode\StandardsSync\Authoring\Package;
use OrthoCode\StandardsSync\Authoring\Standard;
use OrthoCode\StandardsSync\Core\Rule\FileTarget;
use OrthoCode\StandardsSync\Rules\Composer\ConfigSetting\ComposerConfigSetting;
use OrthoCode\StandardsSync\Rules\Composer\Script\ComposerScript;
use OrthoCode\StandardsSync\Rules\General\ManagedBlock\Label;
use OrthoCode\StandardsSync\Rules\General\ManagedBlock\ManagedBlock;

/** OrthoCode's standard for library packages; applications get their own tier. */
final class PackageStandard extends Standard
{
    private const string LABEL = 'ortho-code';

    protected function enforce(Package $package): void
    {
        $this->enforceEditorConfig($package);
        $this->enforceGitignore($package);
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

    /** Synced configs enforce nothing until something runs them: app-checks is the entry point developers and CI call by name, and every family added later appends to it. */
    private function enforceToolchain(): void
    {
        $this->addRule(new ComposerConfigSetting(setting: 'sort-packages', value: true));
        // Composer puts vendor/bin on PATH for scripts, so entries name the bare binary.
        $this->addRule(new ComposerScript(name: 'app-sync', commands: ['standards-sync sync']));
        $this->addRule(new ComposerScript(name: 'app-sync-check', commands: ['standards-sync sync --check']));
        $this->addRule(new ComposerScript(name: 'app-checks', commands: ['@app-sync-check']));
    }
}
