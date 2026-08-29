<?php

declare(strict_types=1);

namespace Tests\OrthoCode\CodingStandards;

use OrthoCode\CodingStandards\ProjectBitbucketStandard;
use OrthoCode\CodingStandards\ProjectStandard;
use OrthoCode\StandardsSync\Authoring\Package;
use OrthoCode\StandardsSync\Core\Config\SyncConfig;
use OrthoCode\StandardsSync\Core\Rule\Rule;
use OrthoCode\StandardsSync\Rules\Composer\Script\ComposerScript;
use OrthoCode\StandardsSync\Testing\SyncTester;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/** Checks the Bitbucket companion, and the seam it shares with the tier it is declared beside. */
#[CoversClass(ProjectBitbucketStandard::class)]
final class ProjectBitbucketStandardTest extends TestCase
{
    public function testSyncingDropsTheManagedPipelineBlock(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./bitbucket-pipelines.yml', $result);
        self::assertStringContainsString('ortho-code', $result['./bitbucket-pipelines.yml']);
        self::assertStringContainsString('pipelines:', $result['./bitbucket-pipelines.yml']);
    }

    // The companion carries CI and nothing else: everything a repository installs comes from the tier beside it.
    public function testTheCompanionTouchesNoFileTheTierOwns(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertSame(['./bitbucket-pipelines.yml'], array_keys($result));
    }

    // The pipeline calls the script by name across a standard boundary, where nothing in the engine ties the two together at all.
    public function testThePipelineCallsTheScriptTheTierDeclares(): void
    {
        $aggregate = array_find(
            (new ProjectStandard($this->package()))->rules(),
            static fn(Rule $rule): bool => $rule instanceof ComposerScript && $rule->name() === 'app-checks',
        );
        self::assertInstanceOf(ComposerScript::class, $aggregate);

        $result = (new SyncTester())->sync($this->config());

        self::assertStringContainsString(sprintf('composer %s', $aggregate->name()), $result['./bitbucket-pipelines.yml']);
    }

    // The runtime the pipeline image provides is the floor the tier requires; the two are raised together.
    public function testThePipelineRunsTheTiersRuntimeFloor(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertStringContainsString('php:8.2', $result['./bitbucket-pipelines.yml']);
    }

    private function config(): SyncConfig
    {
        return SyncConfig::create()->withRuleSet(new ProjectBitbucketStandard($this->package()));
    }

    private function package(): Package
    {
        return new Package(dirname(__DIR__), 'vendor/ortho-code/coding-standards');
    }
}
