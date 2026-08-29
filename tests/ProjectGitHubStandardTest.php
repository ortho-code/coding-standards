<?php

declare(strict_types=1);

namespace Tests\OrthoCode\CodingStandards;

use OrthoCode\CodingStandards\ProjectGitHubStandard;
use OrthoCode\CodingStandards\ProjectStandard;
use OrthoCode\StandardsSync\Authoring\Package;
use OrthoCode\StandardsSync\Core\Config\SyncConfig;
use OrthoCode\StandardsSync\Core\Rule\Rule;
use OrthoCode\StandardsSync\Rules\Composer\Script\ComposerScript;
use OrthoCode\StandardsSync\Testing\SyncTester;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/** Checks the GitHub companion, and the seam it shares with the tier it is declared beside. */
#[CoversClass(ProjectGitHubStandard::class)]
final class ProjectGitHubStandardTest extends TestCase
{
    public function testSyncingDropsTheManagedWorkflowBlock(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertArrayHasKey('./.github/workflows/standards.yml', $result);
        self::assertStringContainsString('ortho-code', $result['./.github/workflows/standards.yml']);
    }

    // The companion carries CI and nothing else: everything a repository installs comes from the tier beside it.
    public function testTheCompanionTouchesNoFileTheTierOwns(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertSame(['./.github/workflows/standards.yml'], array_keys($result));
    }

    // The workflow calls the script by name across a standard boundary, where nothing in the engine ties the two together at all.
    public function testTheWorkflowCallsTheScriptTheTierDeclares(): void
    {
        $aggregate = array_find(
            (new ProjectStandard($this->package()))->rules(),
            static fn(Rule $rule): bool => $rule instanceof ComposerScript && $rule->name() === 'app-checks',
        );
        self::assertInstanceOf(ComposerScript::class, $aggregate);

        $result = (new SyncTester())->sync($this->config());

        self::assertStringContainsString(sprintf('composer %s', $aggregate->name()), $result['./.github/workflows/standards.yml']);
    }

    // The runtime the workflow installs is the floor the tier requires; the two are raised together.
    public function testTheWorkflowRunsTheTiersRuntimeFloor(): void
    {
        $result = (new SyncTester())->sync($this->config());

        self::assertStringContainsString('php-version: \'8.2\'', $result['./.github/workflows/standards.yml']);
    }

    private function config(): SyncConfig
    {
        return SyncConfig::create()->withRuleSet(new ProjectGitHubStandard($this->package()));
    }

    private function package(): Package
    {
        return new Package(dirname(__DIR__), 'vendor/ortho-code/coding-standards');
    }
}
