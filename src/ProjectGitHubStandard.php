<?php

declare(strict_types=1);

namespace OrthoCode\CodingStandards;

use OrthoCode\StandardsSync\Authoring\Package;
use OrthoCode\StandardsSync\Authoring\Standard;
use OrthoCode\StandardsSync\Core\Rule\FileTarget;
use OrthoCode\StandardsSync\Rules\General\ManagedBlock\Label;
use OrthoCode\StandardsSync\Rules\General\ManagedBlock\ManagedBlock;
use Override;

/**
 * The GitHub half of the application standard: the workflow that runs the checks.
 * Declared beside ProjectStandard, never instead of it — this tier carries CI and nothing else, so an application on another forge takes that forge's companion and keeps the rest.
 */
final class ProjectGitHubStandard extends Standard
{
    private const string LABEL = 'ortho-code';

    #[Override]
    protected function enforce(Package $package): void
    {
        // The workflow calls app-checks by name and nothing in the engine ties the two together, so the suite pins it.
        $this->addRule(new ManagedBlock(
            target: FileTarget::fromString('.github/workflows/standards.yml'),
            label: Label::fromString(self::LABEL),
            content: $package->read('project/ci-standards.yml'),
        ));
    }
}
