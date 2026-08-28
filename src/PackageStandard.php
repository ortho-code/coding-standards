<?php

declare(strict_types=1);

namespace OrthoCode\CodingStandards;

use OrthoCode\StandardsSync\Authoring\Package;
use OrthoCode\StandardsSync\Authoring\Standard;
use OrthoCode\StandardsSync\Core\Rule\FileTarget;
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
}
