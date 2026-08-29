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
 * The Bitbucket half of the application standard: the pipeline that runs the checks.
 * Declared beside ProjectStandard, never instead of it — this tier carries CI and nothing else, so an application on another forge takes that forge's companion and keeps the rest.
 */
final class ProjectBitbucketStandard extends Standard
{
    private const string LABEL = 'ortho-code';

    #[Override]
    protected function enforce(Package $package): void
    {
        // Bitbucket reads one file for the whole pipeline definition, so the block is that file rather than a section of it.
        $this->addRule(new ManagedBlock(
            target: FileTarget::fromString('bitbucket-pipelines.yml'),
            label: Label::fromString(self::LABEL),
            content: $package->read('project/ci-pipelines.yml'),
        ));
    }
}
